<?php namespace Franzose\ClosureTable\Tests;

use \Mockery;
use \Illuminate\Container\Container as App;
use \Franzose\ClosureTable\Extensions\Collection;
use \Franzose\ClosureTable\Models\Entity;
use \Franzose\ClosureTable\Models\ClosureTable;
use \Franzose\ClosureTable\Tests\Models\Page;

class EntityTestCase extends BaseTestCase {

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

    /**
     * Children relation index.
     *
     * @var string
     */
    protected $childrenRelationIndex;

    public function setUp()
    {
        parent::setUp();

        Entity::boot();

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
        $result = $this->entity->moveTo(5, $ancestor);

        $this->assertSame($this->entity, $result);
        $this->assertEquals(5, $result->position);
        $this->assertEquals(1, $result->parent_id);
        $this->assertEquals($this->entity->getParent()->getKey(), $ancestor->getKey());
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
    }

    public function testGetAncestorsWhere()
    {
        $entity = Entity::find(12);
        $ancestors = $entity->getAncestorsWhere('excerpt', '=', '');

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $ancestors);
        $this->assertCount(0, $ancestors);

        $ancestors = $entity->getAncestorsWhere($this->entity->getPositionColumn(), '=', 0);
        $this->assertCount(2, $ancestors);
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
    }

    public function testGetDescendantsWhere()
    {
        $entity = Entity::find(9);

        $descendants = $entity->getDescendantsWhere($this->entity->getPositionColumn(), '=', 1);
        $this->assertCount(1, $descendants);
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
        $child  = $entity->getChildAt(2);

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(2, $child->position);
    }

    public function testGetFirstChild()
    {
        $entity = Entity::find(9);
        $child  = $entity->getFirstChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(0, $child->position);
    }

    public function testGetLastChild()
    {
        $entity = Entity::find(9);
        $child  = $entity->getLastChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(3, $child->position);
    }

    public function testGetChildrenRange()
    {
        $entity   = Entity::find(9);
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

        $siblings = $entity->getSiblingsRange(0, 3);

        $this->assertEquals(16, $siblings[1]->getKey());
        $this->assertEquals(17, $siblings[2]->getKey());
        $this->assertEquals(18, $siblings[3]->getKey());
    }

    public function testGetRoots()
    {
        $roots = Entity::getRoots();

        $this->assertCount(9, $roots);

        foreach($roots as $idx => $root)
        {
            $this->assertEquals($idx+1, $roots->get($idx)->getKey());
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

        $this->assertNull(Entity::find(10));
        $this->assertNull(Entity::find(11));
        $this->assertNull(Entity::find(12));
        $this->assertNotNull(Entity::find(8));
    }

    public function testDeleteSubtreeWithAncestor()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree(true);

        $this->assertNull(Entity::find(9));
        $this->assertNull(Entity::find(10));
        $this->assertNull(Entity::find(11));
        $this->assertNull(Entity::find(12));
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
} 