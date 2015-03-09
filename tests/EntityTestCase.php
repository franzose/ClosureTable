<?php
namespace Franzose\ClosureTable\Tests;

use DB;
use Franzose\ClosureTable\Models\ClosureTable;
use Mockery;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\Models\Page;

class EntityTestCase extends BaseTestCase
{
    /**
     * Tested entity.
     *
     * @var Entity;
     */
    protected $entity;

    /**
     * Mocked closure object.
     *
     * @var Mockery\MockInterface|\Yay_MockObject
     */
    protected $closure;

    protected static $force_boot = false;

    /**
     * Children relation index.
     *
     * @var string
     */
    protected $childrenRelationIndex;

    public function setUp()
    {
        parent::setUp();

        // TODO: Remove this when Laravel fixes the issue with model booting in tests
        if (self::$force_boot) {
            Entity::boot();
            Page::boot();
        } else {
            self::$force_boot = true;
        }

        $this->entity = new Entity;
        $this->entity->fillable(['title', 'excerpt', 'body', 'position', 'real_depth']);

        $this->childrenRelationIndex = $this->entity->getChildrenRelationIndex();
    }

    public function testPositionIsFillable()
    {
        $this->assertContains($this->entity->getPositionColumn(), $this->entity->getFillable());
    }

    public function testPositionDefaultValue()
    {
        $this->assertEquals(0, $this->entity->position);
    }

    public function testRealDepthIsFillable()
    {
        $this->assertContains($this->entity->getRealDepthColumn(), $this->entity->getFillable());
    }

    public function testRealDepthDefaultValue()
    {
        $this->assertEquals(0, $this->entity->real_depth);
    }

    public function testIsParent()
    {
        $this->assertFalse($this->entity->isParent());
    }

    public function testIsRoot()
    {
        $this->assertFalse($this->entity->isRoot());
        $this->assertTrue(Entity::find(1)->isRoot());
    }

    public function testCreate()
    {
        DB::statement("SET foreign_key_checks=0");
        ClosureTable::truncate();
        Entity::truncate();
        DB::statement("SET foreign_key_checks=1");

        $entity1 = new Entity;
        $entity1->save();

        $this->assertEquals(0, $entity1->position);

        $entity2 = new Entity;
        $entity2->save();
        $this->assertEquals(1, $entity2->position);
    }

    public function testCreateSetsPosition()
    {
        $entity = new Page(['title' => 'Item 1']);

        $this->assertEquals(null, $entity->position);
        $this->assertEquals(null, $this->readAttribute($entity, 'old_position'));
        $this->assertEquals(null, $entity->parent_id);
        $this->assertEquals(null, $this->readAttribute($entity, 'old_parent_id'));

        $entity->save();

        $this->assertEquals(9, $entity->position);
        $this->assertEquals($entity->position, $this->readAttribute($entity, 'old_position'));
        $this->assertEquals(null, $entity->parent_id);
        $this->assertEquals($entity->parent_id, $this->readAttribute($entity, 'old_parent_id'));
    }

    /**
     * @dataProvider createUseGivenPositionProvider
     */
    public function testCreateUseGivenPosition($initial_position, $test_entity, $assign_position, $expected_position, $test_position)
    {
        $this->assertEquals($initial_position, Page::find($test_entity)->position, 'Prerequisite doesn\'t match expectation');

        $entity = new Page(['title' => 'Item 1']);
        $entity->position = $assign_position;
        $entity->save();

        $this->assertEquals($expected_position, $entity->position, 'Saved position should match expected position');
        $this->assertEquals($test_position, Page::find($test_entity)->position, 'Test entity should have expected position');
    }

    public function createUseGivenPositionProvider()
    {
        return [
            [0, 1, -1, 0, 1, 1], // Negative clamps to 0
            [0, 1, 0, 0, 1], // 0 moves previous 0 to 1
            [3, 4, 3, 3, 4], // Test in mid range
            [8, 9, 8, 8, 9], // Last existing entity
            [8, 9, 9, 9, 8], // Add after last position
            [8, 9, null, 9, 8], // Do not specify position = after last position
        ];
    }

    public function testCreateDoesNotChangePositionOfSiblings()
    {
        $entity1 = new Page(['title' => 'Item 1']);
        $entity1->save();

        $id = $entity1->getKey();

        $entity2 = new Page(['title' => 'Item 2']);
        $entity2->save();

        $this->assertEquals(10, $entity2->position);
        $this->assertEquals(9, Entity::find($id)->position);
    }

    public function testCreateSetsRealDepth()
    {
        $entity = new Page(['title' => 'Item 3']);
        $entity->parent_id = 9;
        $entity->save();

        $this->assertEquals(1, $entity->real_depth);
    }

    public function testSavingLoadedEntityShouldNotTriggerReordering()
    {
        $entity1 = new Page(['title' => 'Item 1']);
        $entity1->save();

        $id = $entity1->getKey();

        $entity1 = Page::find($id);

        $this->assertEquals(8, Page::find(9)->position); // Sibling node that shouldn't move

        $this->assertEquals($entity1->position, $this->readAttribute($entity1, 'old_position'), 'Position should be the same after a load');
        $this->assertEquals($entity1->parent_id, $this->readAttribute($entity1, 'old_parent_id'), 'Parent should be the same after a load');

        $entity1->title = 'New title';
        $entity1->save();

        $this->assertEquals(8, Page::find(9)->position, 'Sibling node should not have moved');
        $this->assertEquals($entity1->position, $this->readAttribute($entity1, 'old_position'));
        $this->assertEquals($entity1->parent_id, $this->readAttribute($entity1, 'old_parent_id'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMoveToThrowsException()
    {
        $this->entity->moveTo(0, $this->entity);
    }

    public function testMoveTo()
    {
        $ancestor = Entity::find(1);
        $result = $this->entity->moveTo(0, $ancestor);

        $this->assertSame($this->entity, $result);
        $this->assertEquals(0, $result->position);
        $this->assertEquals(1, $result->parent_id);
        $this->assertEquals($this->entity->getParent()->getKey(), $ancestor->getKey());
    }

    public function testClampPosition()
    {
        $ancestor = Entity::find(9);
        $entity = Entity::find(15);
        $entity->position = -1;
        $entity->save();

        $this->assertEquals(0, $entity->position);

        $entity->position = 100;
        $entity->save();

        $this->assertEquals($ancestor->countChildren(), $entity->position);
    }

    public function testGetParent()
    {
        $entity = Entity::find(10);
        $parent = $entity->getParent();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $parent);
        $this->assertEquals(9, $parent->getKey());
    }

    public function testGetParentAfterMovingToAnAncestor()
    {
        $entity = Entity::find(10);
        $entity->moveTo(0, 15);
        $parent = $entity->getParent();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $parent);
        $this->assertEquals(15, $parent->getKey());
    }

    public function testGetAncestors()
    {
        $entity = Entity::find(12);
        $ancestors = $entity->getAncestors();

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $ancestors);
        $this->assertCount(3, $ancestors);
        $this->assertArrayValuesEquals($ancestors->modelKeys(), [9, 10, 11]);
    }

    public function testGetAncestorsWhere()
    {
        $entity = Entity::find(12);
        $ancestors = $entity->getAncestorsWhere('excerpt', '=', '');

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $ancestors);
        $this->assertCount(0, $ancestors);

        $ancestors = $entity->getAncestorsWhere($this->entity->getPositionColumn(), '=', 0);
        $this->assertCount(2, $ancestors);
        $this->assertArrayValuesEquals($ancestors->modelKeys(), [10, 11]);
    }

    public function testCountAncestors()
    {
        $entity = Entity::find(12);
        $ancestors = $entity->countAncestors();

        $this->assertEquals(3, $ancestors);
    }

    public function testHasAncestors()
    {
        $entity = Entity::find(12);
        $hasAncestors = $entity->hasAncestors();

        $this->assertTrue($hasAncestors);
    }

    public function testGetDescendants()
    {
        $entity = Entity::find(9);
        $descendants = $entity->getDescendants();

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $descendants);
        $this->assertCount(6, $descendants);
        $this->assertArrayValuesEquals($descendants->modelKeys(), [10, 11, 12, 13, 14, 15]);
    }

    public function testGetDescendantsWhere()
    {
        $entity = Entity::find(9);

        $descendants = $entity->getDescendantsWhere($this->entity->getPositionColumn(), '=', 1);
        $this->assertCount(1, $descendants);
        $this->assertArrayValuesEquals($descendants->modelKeys(), [13]);
    }

    public function testCountDescendants()
    {
        $entity = Entity::find(9);
        $descendants = $entity->countDescendants();

        $this->assertEquals(6, $descendants);
    }

    public function testHasDescendants()
    {
        $entity = Entity::find(9);
        $hasDescendants = $entity->hasDescendants();

        $this->assertTrue($hasDescendants);
    }

    public function testGetChildren()
    {
        $entity = Entity::find(9);
        $children = $entity->getChildren();

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $children);
        $this->assertCount(4, $children);
        $this->assertArrayValuesEquals($children->modelKeys(), [10, 13, 14, 15]);
    }

    public function testCountChildren()
    {
        $entity = Entity::find(9);
        $children = $entity->countChildren();

        $this->assertEquals(4, $children);
    }

    public function testGetChildAt()
    {
        $entity = Entity::find(9);
        $child = $entity->getChildAt(2);

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(2, $child->position);
    }

    public function testGetFirstChild()
    {
        $entity = Entity::find(9);
        $child = $entity->getFirstChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(0, $child->position);
    }

    public function testGetLastChild()
    {
        $entity = Entity::find(9);
        $child = $entity->getLastChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(3, $child->position);
    }

    public function testGetChildrenRange()
    {
        $entity = Entity::find(9);
        $children = $entity->getChildrenRange(0, 2);

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $children);
        $this->assertCount(3, $children);
        $this->assertEquals(0, $children[0]->position);
        $this->assertEquals(1, $children[1]->position);
        $this->assertEquals(2, $children[2]->position);

        $children = $entity->getChildrenRange(2);

        $this->assertCount(2, $children);
        $this->assertEquals(2, $children[0]->position);
        $this->assertEquals(3, $children[1]->position);
    }

    public function testAddChildWithPosition()
    {
        $entity = Entity::find(15);
        $newone = new Entity;
        $result = $entity->addChild($newone, 0);

        $this->assertEquals(0, $newone->position);
        $this->assertTrue($entity->isParent());
        $this->assertSame($entity, $result);
    }

    public function testAddChildWithoutPosition()
    {
        $entity = Entity::find(9);
        $newone = new Entity;
        $result = $entity->addChild($newone);

        $this->assertEquals(4, $newone->position);
        $this->assertTrue($entity->isParent());
        $this->assertSame($entity, $result);
    }

    public function testAddChildren()
    {
        $entity = Entity::find(15);
        $child1 = new Entity;
        $child2 = new Entity;
        $child3 = new Entity;

        $result = $entity->addChildren([$child1, $child2, $child3]);

        $this->assertSame($entity, $result);
        $this->assertEquals(3, $entity->countChildren());

        $this->assertEquals(0, $child1->position);
        $this->assertEquals(1, $child2->position);
        $this->assertEquals(2, $child3->position);
    }

    public function testRemoveChild()
    {
        $entity = Entity::find(9);
        $entity->removeChild(0);

        $child = Entity::find(10);

        $this->assertNull($child);
        $this->assertEquals(3, $entity->countChildren());
    }

    public function testRemoveChildren()
    {
        $entity = Entity::find(9);
        $entity->removeChildren(0, 1);

        $this->assertEquals(2, $entity->countChildren());
    }

    public function testRemoveChildrenToTheEnd()
    {
        $entity = Entity::find(9);
        $entity->removeChildren(1);

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $entity->getFirstChild());
        $this->assertEquals(1, $entity->countChildren());
    }

    public function testGetSiblings()
    {
        $entity = Entity::find(13);
        $siblings = $entity->getSiblings();

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $siblings);
        $this->assertCount(3, $siblings);
        $this->assertEquals(10, $siblings[0]->getKey());
        $this->assertEquals(14, $siblings[1]->getKey());
        $this->assertEquals(15, $siblings[2]->getKey());
    }

    public function testsCountSiblings()
    {
        $entity = Entity::find(13);
        $number = $entity->countSiblings();

        $this->assertEquals(3, $number);
    }

    public function testsHasSiblings()
    {
        $entity = Entity::find(13);
        $hasSiblings = $entity->hasSiblings();

        $this->assertTrue($hasSiblings);
    }

    public function testsGetNeighbors()
    {
        $entity = Entity::find(13);
        $neighbors = $entity->getNeighbors();

        $this->assertCount(2, $neighbors);
        $this->assertEquals(10, $neighbors[0]->getKey());
        $this->assertEquals(14, $neighbors[1]->getKey());
    }

    public function testsGetSiblingAt()
    {
        $entity = Entity::find(13);
        $sibling = $entity->getSiblingAt(0);

        $this->assertEquals(10, $sibling->getKey());

        $sibling = $entity->getSiblingAt(2);

        $this->assertEquals(14, $sibling->getKey());
    }

    public function testGetFirstSibling()
    {
        $entity = Entity::find(13);
        $sibling = $entity->getFirstSibling();

        $this->assertEquals(10, $sibling->getKey());
    }

    public function testGetLastSibling()
    {
        $entity = Entity::find(13);
        $sibling = $entity->getLastSibling();

        $this->assertEquals(15, $sibling->getKey());
    }

    public function testGetPrevSibling()
    {
        $entity = Entity::find(15);
        $sibling = $entity->getPrevSibling();

        $this->assertEquals(14, $sibling->getKey());
    }

    public function testGetPrevSiblings()
    {
        $entity = Entity::find(15);
        $siblings = $entity->getPrevSiblings();

        $this->assertCount(3, $siblings);
        $this->assertEquals(10, $siblings[0]->getKey());
        $this->assertEquals(13, $siblings[1]->getKey());
        $this->assertEquals(14, $siblings[2]->getKey());
    }

    public function testsCountPrevSiblings()
    {
        $entity = Entity::find(15);
        $siblings = $entity->countPrevSiblings();

        $this->assertEquals(3, $siblings);
    }

    public function testsHasPrevSiblings()
    {
        $entity = Entity::find(15);
        $hasPrevSiblings = $entity->hasPrevSiblings();

        $this->assertTrue($hasPrevSiblings);
    }

    public function testGetNextSibling()
    {
        $entity = Entity::find(10);
        $sibling = $entity->getNextSibling();

        $this->assertEquals(13, $sibling->getKey());
    }

    public function testGetNextSiblings()
    {
        $entity = Entity::find(10);
        $siblings = $entity->getNextSiblings();

        $this->assertCount(3, $siblings);
        $this->assertEquals(13, $siblings[0]->getKey());
        $this->assertEquals(14, $siblings[1]->getKey());
        $this->assertEquals(15, $siblings[2]->getKey());
    }

    public function testCountNextSiblings()
    {
        $entity = Entity::find(10);
        $siblings = $entity->countNextSiblings();

        $this->assertEquals(3, $siblings);
    }

    public function testsHasNextSiblings()
    {
        $entity = Entity::find(10);
        $hasNextSiblings = $entity->hasNextSiblings();

        $this->assertTrue($hasNextSiblings);
    }

    public function testGetSiblingsRange()
    {
        $entity = Entity::find(15);
        $siblings = $entity->getSiblingsRange(1, 2);

        $this->assertCount(2, $siblings);
        $this->assertEquals(1, $siblings[0]->position);
        $this->assertEquals(2, $siblings[1]->position);
    }

    public function testAddSibling()
    {
        $entity = Entity::find(15);
        $entity->addSibling(new Entity);

        $sibling = $entity->getNextSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $sibling);
        $this->assertEquals(4, $sibling->position);
    }

    public function testAddSiblings()
    {
        $entity = Entity::find(15);
        $entity->addSiblings([new Entity, new Entity, new Entity]);

        $siblings = $entity->getNextSiblings();

        $this->assertCount(3, $siblings);
        $this->assertEquals(4, $siblings[0]->position);
        $this->assertEquals(5, $siblings[1]->position);
        $this->assertEquals(6, $siblings[2]->position);
    }

    public function testAddSiblingsFromPosition()
    {
        $entity = Entity::find(15);

        $entity->addSiblings([new Entity, new Entity, new Entity, new Entity], 1);

        $siblings = $entity->getSiblingsRange(1, 4);

        $this->assertEquals(16, $siblings[0]->getKey());
        $this->assertEquals(17, $siblings[1]->getKey());
        $this->assertEquals(18, $siblings[2]->getKey());
        $this->assertEquals(19, $siblings[3]->getKey());
    }

    public function testGetRoots()
    {
        $roots = Entity::getRoots();

        $this->assertCount(9, $roots);

        foreach ($roots as $idx => $root) {
            $this->assertEquals($idx + 1, $roots->get($idx)->getKey());
        }
    }

    public function testGetTree()
    {
        $tree = Entity::getTree();

        $this->assertCount(9, $tree);

        $ninth = $tree[8];
        $this->assertArrayHasKey($this->childrenRelationIndex, $ninth->getRelations());

        $tenth = $ninth->getChildren();

        $this->assertCount(4, $tenth);
    }

    public function testGetTreeWhere()
    {
        $tree = Entity::getTreeWhere($this->entity->getPositionColumn(), '>=', 1, [
            $this->entity->getKeyName(),
            $this->entity->getPositionColumn()
        ]);

        $this->assertCount(8, $tree);
        $this->assertEquals(1, $tree[0]->position);

        $eight = $tree[7];

        $this->assertArrayHasKey($this->childrenRelationIndex, $eight->getRelations());
        $this->assertEquals(1, $eight->getChildAt(0)->position);

        $ninth = $eight->getChildren();

        $this->assertCount(3, $ninth);
    }

    public function testDeleteSubtree()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree();

        $this->assertEquals(1, Entity::whereBetween('id', [9, 15])->count());
        $this->assertEquals(8, Entity::whereBetween('id', [1, 8])->count());
    }

    public function testDeleteSubtreeWithAncestor()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree(true);

        $this->assertEquals(0, Entity::whereBetween('id', [9, 15])->count());
        $this->assertEquals(8, Entity::whereBetween('id', [1, 8])->count());
    }

    public function testForceDeleteSubtree()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree(false, true);

        $this->assertEquals(1, Entity::whereBetween('id', [9, 15])->count());
        $this->assertEquals(1, ClosureTable::whereBetween('ancestor', [9, 15])->count());
    }

    public function testForceDeleteDeepSubtree()
    {
        Entity::find(9)->moveTo(0, 8);
        Entity::find(8)->moveTo(0, 7);
        Entity::find(7)->moveTo(0, 6);
        Entity::find(6)->moveTo(0, 5);
        Entity::find(5)->moveTo(0, 4);
        Entity::find(4)->moveTo(0, 3);
        Entity::find(3)->moveTo(0, 2);
        Entity::find(2)->moveTo(0, 1);

        Entity::find(1)->deleteSubtree(false, true);

        $this->assertEquals(1, Entity::whereBetween('id', [1, 9])->count());
        $this->assertEquals(1, ClosureTable::whereBetween('ancestor', [1, 9])->count());
    }

    public function testForceDeleteSubtreeWithSelf()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree(true, true);

        $this->assertEquals(0, Entity::whereBetween('id', [9, 15])->count());
        $this->assertEquals(0, ClosureTable::whereBetween('ancestor', [9, 15])->count());
    }

    public function testCreateFromArray()
    {
        $array = [
            [
                'id' => 90,
                'title' => 'About',
                'position' => 0,
                'children' => [
                    [
                        'id' => 93,
                        'title' => 'Testimonials'
                    ]
                ]
            ],
            [
                'id' => 91,
                'title' => 'Blog',
                'position' => 1
            ],
            [
                'id' => 92,
                'title' => 'Portfolio',
                'position' => 2
            ],
        ];

        $pages = Page::createFromArray($array);

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $pages);
        $this->assertCount(3, $pages);

        $pageZero = $pages[0];

        $this->assertTrue($pageZero->hasChildrenRelation());

        $this->assertEquals(90, $pageZero->getKey());
        $this->assertEquals(91, $pages[1]->getKey());
        $this->assertEquals(92, $pages[2]->getKey());
        $this->assertEquals(93, $pageZero->getChildAt(0)->getKey());
    }

    public function testCreateFromArrayBug81()
    {
        $array = [
            [
                'title' => 'About',
                'children' => [
                    [
                        'title' => 'Testimonials',
                        'children' => [
                            [
                                'title' => 'child 1',
                            ],
                            [
                                'title' => 'child 2',
                            ],
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Blog',
            ],
            [
                'title' => 'Portfolio',
            ],
        ];

        $pages = Page::createFromArray($array);

        $about = $pages[0];
        $this->assertEquals('About', $about->title);
        $this->assertEquals(1, $about->countChildren());
        $this->assertEquals(16, $about->getKey());

        $blog = $pages[1];
        $this->assertEquals('Blog', $blog->title);
        $this->assertEquals(0, $blog->countChildren());
        $this->assertEquals(20, $blog->getKey());

        $portfolio = $pages[2];
        $this->assertEquals('Portfolio', $portfolio->title);
        $this->assertEquals(0, $portfolio->countChildren());
        $this->assertEquals(21, $portfolio->getKey());

        $pages = $pages[0]->getChildren();

        $testimonials = $pages[0];
        $this->assertEquals('Testimonials', $testimonials->title);
        $this->assertEquals(2, $testimonials->countChildren());
        $this->assertEquals(17, $testimonials->getKey());

        $pages = $pages[0]->getChildren();

        $child1 = $pages[0];
        $this->assertEquals('child 1', $child1->title);
        $this->assertEquals(0, $child1->countChildren());
        $this->assertEquals(18, $child1->getKey());

        $child2 = $pages[1];
        $this->assertEquals('child 2', $child2->title);
        $this->assertEquals(0, $child2->countChildren());
        $this->assertEquals(19, $child2->getKey());
    }

    public function testInsertNode()
    {
        $entity = Entity::create(['title' => 'abcde']);
        $closure = ClosureTable::whereDescendant($entity->getKey())->first();

        $this->assertNotNull($closure);
        $this->assertEquals($entity->getKey(), $closure->ancestor);
        $this->assertEquals(0, $closure->depth);
    }

    public function testInsertedNodeDepth()
    {
        $entity = Entity::create(['title' => 'abcde']);
        $child = Entity::create(['title' => 'abcde']);
        $child->moveTo(0, $entity);

        $closure = ClosureTable::whereDescendant($child->getKey())
            ->whereAncestor($entity->getKey())->first();

        $this->assertNotNull($closure);
        $this->assertEquals(1, $closure->depth);
    }

    public function testValidNumberOfRowsInsertedByInsertNode()
    {
        $ancestor = Entity::create(['title' => 'abcde']);
        $descendant = Entity::create(['title' => 'abcde']);
        $descendant->moveTo(0, $ancestor);

        $ancestorRows = ClosureTable::whereDescendant($ancestor->getKey())->count();
        $descendantRows = ClosureTable::whereDescendant($descendant->getKey())->count();

        $this->assertEquals(1, $ancestorRows);
        $this->assertEquals(2, $descendantRows);
    }

    public function testMoveNodeToAnotherAncestor()
    {
        $descendant = Entity::find(1);
        $descendant->moveTo(0, 2);

        $ancestors = ClosureTable::whereDescendant(2)->count();
        $descendants = ClosureTable::whereDescendant(1)->count();

        $this->assertEquals(1, $ancestors);
        $this->assertEquals(2, $descendants);
    }

    public function testMoveNodeToDeepNesting()
    {
        $item = Entity::find(1);
        $item->moveTo(0, 2);

        $item = Entity::find(2);
        $item->moveTo(0, 3);

        $item = Entity::find(3);
        $item->moveTo(0, 4);

        $item = Entity::find(4);
        $item->moveTo(0, 5);

        $descendantRows = ClosureTable::whereDescendant(1)->count();
        $ancestorRows = ClosureTable::whereDescendant(2)->count();

        $this->assertEquals(4, $ancestorRows);
        $this->assertEquals(5, $descendantRows);
    }

    public function testMoveNodeToBecomeRoot()
    {
        $item = Entity::find(1);
        $item->moveTo(0, 2);

        $item = Entity::find(2);
        $item->moveTo(0, 3);

        $item = Entity::find(3);
        $item->moveTo(0, 4);

        $item = Entity::find(4);
        $item->moveTo(0, 5);

        $item = Entity::find(1);
        $item->moveTo(0);

        $this->assertEquals(1, ClosureTable::whereDescendant(1)->count());
    }
}
