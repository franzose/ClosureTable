<?php namespace Franzose\ClosureTable\Tests;

use \Mockery;
use \Illuminate\Container\Container as App;
use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Franzose\ClosureTable\Models\Entity;
use \Franzose\ClosureTable\Models\ClosureTable;

class EntityTestCase extends BaseTestCase {
    /**
     * @var Entity;
     */
    protected $entity;

    /**
     * @var Mockery\MockInterface|\Yay_MockObject
     */
    protected $closure;

    public function setUp()
    {
        parent::setUp();

        $this->entity = new Entity;
        $this->app->instance('Franzose\ClosureTable\Contracts\ClosureTableInterface', new ClosureTable);
    }

    public function testPositionIsFillable()
    {
        $this->assertContains(Entity::POSITION, $this->entity->getFillable());
    }

    public function testPositionDefaultValue()
    {
        $this->assertEquals(0, $this->entity->{Entity::POSITION});
    }

    public function testIsParent()
    {
        $this->assertFalse($this->entity->isParent());
    }

    public function testIsRoot()
    {
        $this->assertTrue($this->entity->isRoot());
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
        $this->assertEquals(5, $result->{Entity::POSITION});
        $this->assertEquals($this->entity->getParent()->getKey(), $ancestor->getKey());
    }

    public function testGetParent()
    {
        $entity = Entity::find(10);
        $parent = $entity->getParent();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $parent);
        $this->assertEquals(9, $parent->getKey());
    }

    public function testGetAncestors()
    {
        $entity = Entity::find(12);
        $ancestors = $entity->getAncestors();

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $ancestors);
        $this->assertCount(3, $ancestors);
    }

    public function testCountAncestors()
    {
        $entity = Entity::find(12);
        $ancestors = $entity->countAncestors();

        $this->assertInternalType('int', $ancestors);
        $this->assertEquals(3, $ancestors);
    }

    public function testGetDescendants()
    {
        $entity = Entity::find(9);
        $descendants = $entity->getDescendants();

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $descendants);
        $this->assertCount(6, $descendants);
    }

    public function testGetDescendantsTree()
    {
        $entity = Entity::find(9);
        $descendants = $entity->getDescendantsTree();

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $descendants);
        $this->assertCount(1, $descendants);
        $this->assertArrayHasKey('children', $descendants->get(0)->getRelations());
    }

    public function testCountDescendants()
    {
        $entity = Entity::find(9);
        $descendants = $entity->countDescendants();

        $this->assertInternalType('int', $descendants);
        $this->assertEquals(6, $descendants);
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

        $this->assertInternalType('int', $children);
        $this->assertEquals(4, $children);
    }

    public function testGetChildAt()
    {
        $entity = Entity::find(9);
        $child  = $entity->getChildAt(2);

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(2, $child->{EntityInterface::POSITION});
    }

    public function testGetFirstChild()
    {
        $entity = Entity::find(9);
        $child  = $entity->getFirstChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(0, $child->{EntityInterface::POSITION});
    }

    public function testGetLastChild()
    {
        $entity = Entity::find(9);
        $child  = $entity->getLastChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $child);
        $this->assertEquals(3, $child->{EntityInterface::POSITION});
    }

    public function testAppendChild()
    {
        $entity = Entity::find(15);
        $newone = new Entity;
        $result = $entity->appendChild($newone, 0);

        $this->assertEquals(0, $newone->{EntityInterface::POSITION});
        $this->assertTrue($entity->isParent());
        $this->assertSame($entity, $result);
    }

    public function testAppendChildren()
    {
        $entity = Entity::find(15);
        $child1 = new Entity;
        $child1->save();
        $child2 = new Entity;
        $child2->save();
        $child3 = new Entity;
        $child3->save();

        $array = [$child1, $child2, $child3];
        $result = $entity->appendChildren($array);

        $this->assertSame($entity, $result);
        $this->assertNotNull($entity->getChildAt(2));
        $this->assertEquals(3, $entity->getChildren()->count());
        $this->assertEquals(3, $entity->countChildren());
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
        $this->assertEquals(10, $siblings->get(0)->getKey());
        $this->assertEquals(14, $siblings->get(1)->getKey());
        $this->assertEquals(15, $siblings->get(2)->getKey());
    }

    public function testsCountSiblings()
    {
        $entity = Entity::find(13);
        $number = $entity->countSiblings();

        $this->assertEquals(3, $number);
    }

    public function testsGetNeighbors()
    {
        $entity = Entity::find(13);
        $neighbors = $entity->getNeighbors();

        $this->assertCount(2, $neighbors);
        $this->assertEquals(10, $neighbors->get(0)->getKey());
        $this->assertEquals(14, $neighbors->get(1)->getKey());
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
        $this->assertEquals(10, $siblings->get(0)->getKey());
        $this->assertEquals(13, $siblings->get(1)->getKey());
        $this->assertEquals(14, $siblings->get(2)->getKey());
    }

    public function testsCountPrevSiblings()
    {
        $entity = Entity::find(15);
        $siblings = $entity->countPrevSiblings();

        $this->assertEquals(3, $siblings);
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
        $this->assertEquals(13, $siblings->get(0)->getKey());
        $this->assertEquals(14, $siblings->get(1)->getKey());
        $this->assertEquals(15, $siblings->get(2)->getKey());
    }

    public function testCountNextSiblings()
    {
        $entity = Entity::find(10);
        $siblings = $entity->countNextSiblings();

        $this->assertEquals(3, $siblings);
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

        $ninth = $tree->get(8);
        $this->assertArrayHasKey(EntityInterface::CHILDREN, $ninth->getRelations());

        $tenth = $ninth->getRelation(EntityInterface::CHILDREN);
        $this->assertCount(1, $tenth);
    }

    public function testDeleteSubtree()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree();

        $this->assertNull(Entity::find(10));
        $this->assertNull(Entity::find(11));
        $this->assertNull(Entity::find(12));
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
} 