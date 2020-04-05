<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

class ChildManipulationTests extends BaseTestCase
{
    public function testAddChild()
    {
        $leaf = Entity::find(14);
        $child = Entity::find(15);

        $leaf->addChild($child);

        static::assertEquals(14, $child->parent_id);
        static::assertEquals(0, $child->position);
        static::assertTrue($leaf->isParent());
    }

    public function testAddChildShouldReturnChild()
    {
        $leaf = Entity::find(14);
        $child = Entity::find(15);

        $result = $leaf->addChild($child, 0, true);

        static::assertSame($child, $result);
    }

    public function testAddChildToTheLastPosition()
    {
        $parent = Entity::find(9);
        $child = Entity::find(12);

        $parent->addChild($child);

        static::assertEquals(9, $child->parent_id);
        static::assertEquals(4, $child->position);
        static::assertPositions([0, 1, 2, 3], [10, 13, 14, 15]);
    }

    public function testAddChildToPosition()
    {
        $parent = Entity::find(9);
        $child = Entity::find(12);

        $parent->addChild($child, 2);

        static::assertEquals(9, $child->parent_id);
        static::assertEquals(2, $child->position);
        static::assertPositions([0, 1, 3, 4], [10, 13, 14, 15]);
    }

    public function testAddChildren()
    {
        $entity = Entity::find(15);
        $child1 = new Entity();
        $child2 = new Entity();
        $child3 = new Entity();

        $result = $entity->addChildren([
            $child1,
            $child2,
            $child3
        ]);

        static::assertSame($entity, $result);
        static::assertEquals(3, $entity->countChildren());
        static::assertEquals(0, $child1->position);
        static::assertEquals(1, $child2->position);
        static::assertEquals(2, $child3->position);
    }

    public function testAddChildrenFromPosition()
    {
        $entity = Entity::find(9);
        $child1 = new Entity();
        $child2 = new Entity();

        $entity->addChildren([$child1, $child2], 1);

        static::assertEquals(6, $entity->countChildren());
        static::assertPositions([0, 3, 4, 5], [10, 13, 14, 15]);
        static::assertEquals(1, $child1->position);
        static::assertEquals(2, $child2->position);
    }

    public function testRemoveChild()
    {
        $entity = Entity::find(9);

        $entity->removeChild(0);

        static::assertNull(Entity::find(10));
        static::assertEquals(3, $entity->countChildren());
        static::assertPositions([0, 1, 2], [13, 14, 15]);
    }

    public function testRemoveChildren()
    {
        $entity = Entity::find(9);
        $entity->addChild(new Entity());

        $entity->removeChildren(0, 2);

        static::assertEquals(2, $entity->countChildren());
        static::assertPositions([0, 1], [15, 16]);
    }

    public function testRemoveChildrenToTheEnd()
    {
        $entity = Entity::find(9);

        $entity->removeChildren(1);

        static::assertEquals(1, $entity->countChildren());

        $firstChild = $entity->getFirstChild();
        static::assertEquals(10, $firstChild->getKey());
        static::assertEquals(0, $firstChild->position);
    }
}
