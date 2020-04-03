<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

class ChildQueryTests extends BaseTestCase
{
    public function testNewInstance()
    {
        $entity = new Entity();

        static::assertCount(0, $entity->getChildren());
        static::assertEquals(0, $entity->countChildren());
        static::assertFalse($entity->hasChildren());
    }

    public function testGetChildren()
    {
        static::assertCount(4, Entity::find(9)->getChildren());
    }

    public function testCountChildren()
    {
        static::assertEquals(4, Entity::find(9)->countChildren());
    }

    public function testHasChildren()
    {
        static::assertFalse(Entity::find(1)->hasChildren());
        static::assertTrue(Entity::find(9)->hasChildren());
    }

    public function testGetChildAt()
    {
        $child = Entity::find(9)->getChildAt(1);

        static::assertInstanceOf(Entity::class, $child);
        static::assertEquals(13, $child->getKey());
    }

    public function testGetFirstChild()
    {
        $entity = Entity::find(9);

        $child = $entity->getFirstChild();

        static::assertInstanceOf(Entity::class, $child);
        static::assertEquals(10, $child->getKey());
    }

    public function testGetLastChild()
    {
        $entity = Entity::find(9);
        $child = $entity->getLastChild();

        static::assertInstanceOf(Entity::class, $child);
        static::assertEquals(15, $child->getKey());
    }

    public function testGetChildrenRange()
    {
        $entity = Entity::find(9);
        $children = $entity->getChildrenRange(0, 2);

        static::assertInstanceOf(Collection::class, $children);
        static::assertCount(3, $children);
        static::assertEquals(0, $children[0]->position);
        static::assertEquals(1, $children[1]->position);
        static::assertEquals(2, $children[2]->position);

        $children = $entity->getChildrenRange(2);

        static::assertCount(2, $children);
        static::assertEquals(2, $children[0]->position);
        static::assertEquals(3, $children[1]->position);
    }
}
