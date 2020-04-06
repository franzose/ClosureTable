<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

class AncestorTests extends BaseTestCase
{
    public function testGetAncestorsShouldReturnAnEmptyCollection()
    {
        static::assertCount(0, (new Entity())->getAncestors());
        static::assertCount(0, Entity::find(1)->getAncestors());
    }

    public function testGetAncestorsShouldNotBeEmpty()
    {
        $entity = Entity::find(12);

        $ancestors = $entity->getAncestors();

        static::assertInstanceOf(Collection::class, $ancestors);
        static::assertCount(3, $ancestors);
        static::assertContainsOnlyInstancesOf(Entity::class, $ancestors);
        $this->assertArrayValuesEquals($ancestors->modelKeys(), [9, 10, 11]);
    }

    public function testAncestorsWhere()
    {
        $entity = Entity::find(12);

        $ancestors = $entity->getAncestorsWhere('position', '<', 2);

        static::assertInstanceOf(Collection::class, $ancestors);
        static::assertCount(2, $ancestors);
        static::assertContainsOnlyInstancesOf(Entity::class, $ancestors);
        $this->assertArrayValuesEquals($ancestors->modelKeys(), [10, 11]);
    }

    public function testCountAncestors()
    {
        static::assertEquals(0, Entity::find(1)->countAncestors());
        static::assertEquals(3, Entity::find(12)->countAncestors());
    }

    public function testHasAncestors()
    {
        static::assertFalse(Entity::find(1)->hasAncestors());
        static::assertTrue(Entity::find(12)->hasAncestors());
    }
}
