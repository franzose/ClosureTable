<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

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

    public function testAddChild2()
    {
        $parent = Entity::find(11);
        $child = Entity::find(13);

        $parent->addChild($child, 0);

        static::assertEquals(11, $child->parent_id);
        static::assertEquals(0, $child->position);
        static::assertModelAttribute('position', [
            10 => 0,
            14 => 1,
            15 => 2,
            11 => 0,
            12 => 1
        ]);
    }

    public function testAddChildReordersNodesOnThePreviousLevel()
    {
        $parent = Entity::find(13);
        $child = Entity::find(5);

        $parent->addChild($child);

        static::assertModelAttribute('position', [
            5 => 0,
            // previous level nodes
            6 => 4,
            7 => 5,
            8 => 6,
            9 => 7,
        ]);
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
        static::assertModelAttribute('position', [
            10 => 0,
            13 => 1,
            14 => 2,
            15 => 3,
            12 => 4,
        ]);
    }

    public function testAddChildToPosition()
    {
        $parent = Entity::find(9);
        $child = Entity::find(12);

        $parent->addChild($child, 2);

        static::assertEquals(9, $child->parent_id);
        static::assertEquals(2, $child->position);
        static::assertModelAttribute('position', [
            10 => 0,
            13 => 1,
            12 => 2,
            14 => 3,
            15 => 4
        ]);
    }

    public function testAddChildHavingChildren()
    {
        $parent = Entity::find(13);
        $child = Entity::find(10);

        $parent->addChild($child);

        static::assertEquals(13, $child->parent_id);
        static::assertEquals(0, $child->position);
        static::assertModelAttribute('position', [
            13 => 0,
            14 => 1,
            15 => 2,
            10 => 0,
            11 => 0,
            12 => 0
        ]);
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
        static::assertEquals(1, $child1->position);
        static::assertEquals(2, $child2->position);
        static::assertModelAttribute('position', [
            10 => 0,
            13 => 3,
            14 => 4,
            15 => 5
        ]);
    }

    public function testRemoveChild()
    {
        $entity = Entity::find(9);

        $entity->removeChild(0);

        static::assertNull(Entity::find(10));
        static::assertEquals(3, $entity->countChildren());
        static::assertModelAttribute('position', [
            13 => 0,
            14 => 1,
            15 => 2
        ]);
    }

    public function testRemoveChildHavingChildren()
    {
        $entity = Entity::find(9);

        $entity->removeChild(0, true);

        static::assertNull(Entity::find(10));

        $entity11 = Entity::find(11);
        $entity12 = Entity::find(12);

        static::assertTrue($entity11->isRoot());
        static::assertFalse($entity12->isRoot());
    }

    public function testRemoveChildren()
    {
        $entity = Entity::find(9);
        $entity->addChild(new Entity());

        $entity->removeChildren(0, 2);

        static::assertEmpty(Entity::whereIn('id', [10, 13, 14])->get());
        static::assertEquals(2, $entity->countChildren());
        static::assertModelAttribute('position', [
            15 => 0,
            16 => 1
        ]);
    }

    public function testRemoveChildrenToTheEnd()
    {
        $entity = Entity::find(9);

        $entity->removeChildren(1);

        static::assertEmpty(Entity::whereIn('id', [13, 14, 15])->get());
        static::assertEquals(1, $entity->countChildren());

        $firstChild = $entity->getFirstChild();
        static::assertEquals(10, $firstChild->getKey());
        static::assertEquals(0, $firstChild->position);
    }

    public function testRemoveChildrenHavingChildren()
    {
        Entity::find(13)->addChildren([new Entity(), new Entity()]);

        $parent = Entity::find(9);

        $parent->removeChildren(0, 1);

        static::assertEmpty(Entity::whereIn('id', [10, 13])->get());
        static::assertEquals(2, $parent->countChildren());
        static::assertModelAttribute('position', [
            14 => 0,
            15 => 1
        ]);
    }
}
