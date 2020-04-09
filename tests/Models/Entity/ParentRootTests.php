<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

/**
 * @see \Franzose\ClosureTable\Tests\EntitiesSeeder::run()
 */
class ParentRootTests extends BaseTestCase
{
    public function testNewInstance()
    {
        static::assertFalse((new Entity())->isParent());
        static::assertFalse((new Entity())->isRoot());
    }

    public function testExistingInstance()
    {
        static::assertTrue(Entity::find(9)->isParent());
        static::assertFalse(Entity::find(1)->isParent());
        static::assertTrue(Entity::find(1)->isRoot());
        static::assertFalse(Entity::find(10)->isRoot());
    }

    public function testGetParent()
    {
        $parent = Entity::find(10)->getParent();

        static::assertInstanceOf(Entity::class, $parent);
        static::assertEquals(9, $parent->getKey());
        static::assertNull(Entity::find(1)->getParent());
        static::assertNull((new Entity())->getParent());
    }

    public function testGetRoots()
    {
        $roots = Entity::getRoots();

        static::assertCount(9, $roots);

        foreach ($roots as $idx => $root) {
            static::assertEquals($idx + 1, $roots->get($idx)->getKey());
        }
    }

    public function testMakeRoot()
    {
        $child = Entity::find(13);

        $child->makeRoot(4);

        static::assertTrue($child->isRoot());
        static::assertModelAttribute('position', [
            10 => 0,
            14 => 1,
            15 => 2,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9
        ]);
    }
}
