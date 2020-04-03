<?php
namespace Franzose\ClosureTable\Tests;

use DB;
use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;

class EntityTestCase extends BaseTestCase
{
    /**
     * Tested entity.
     *
     * @var Entity;
     */
    protected $entity;

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
        $this->assertEquals(null, $this->readAttribute($entity, 'previousPosition'));
        $this->assertEquals(null, $entity->parent_id);
        $this->assertEquals(null, $this->readAttribute($entity, 'previousParentId'));

        $entity->save();

        $this->assertEquals(9, $entity->position);
        $this->assertEquals($entity->position, $this->readAttribute($entity, 'previousPosition'));
        $this->assertEquals(null, $entity->parent_id);
        $this->assertEquals($entity->parent_id, $this->readAttribute($entity, 'previousParentId'));
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

        $this->assertEquals($entity1->position, $this->readAttribute($entity1, 'previousPosition'), 'Position should be the same after a load');
        $this->assertEquals($entity1->parent_id, $this->readAttribute($entity1, 'previousParentId'), 'Parent should be the same after a load');

        $entity1->title = 'New title';
        $entity1->save();

        $this->assertEquals(8, Page::find(9)->position, 'Sibling node should not have moved');
        $this->assertEquals($entity1->position, $this->readAttribute($entity1, 'previousPosition'));
        $this->assertEquals($entity1->parent_id, $this->readAttribute($entity1, 'previousParentId'));
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

    public function testGetParentAfterMovingToAnAncestor()
    {
        $entity = Entity::find(10);
        $entity->moveTo(0, 15);
        $parent = $entity->getParent();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $parent);
        $this->assertEquals(15, $parent->getKey());
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
