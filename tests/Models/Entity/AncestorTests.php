<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

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

    public function testAncestorsScope()
    {
        $entity = Entity::find(12);

        $ancestors = $entity->ancestors()->get();

        static::assertCount(3, $ancestors);
        static::assertEquals([11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsOfScope()
    {
        $ancestors = Entity::ancestorsOf(12)->get();

        static::assertCount(3, $ancestors);
        static::assertEquals([11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsWithSelfScope()
    {
        $entity = Entity::find(12);

        $ancestors = $entity->ancestorsWithSelf()->get();

        static::assertCount(4, $ancestors);
        static::assertEquals([12, 11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsWithSelfOfScope()
    {
        $ancestors = Entity::ancestorsWithSelfOf(12)->get();

        static::assertCount(4, $ancestors);
        static::assertEquals([12, 11, 10, 9], $ancestors->modelKeys());
    }

    public function testGetAncestorsShouldNotBeEmpty()
    {
        $entity = Entity::find(12);

        $ancestors = $entity->getAncestors();

        static::assertInstanceOf(Collection::class, $ancestors);
        static::assertCount(3, $ancestors);
        static::assertContainsOnlyInstancesOf(Entity::class, $ancestors);
        static::assertEquals([11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsWhere()
    {
        $entity = Entity::find(12);

        $ancestors = $entity->getAncestorsWhere('position', '<', 2);

        static::assertInstanceOf(Collection::class, $ancestors);
        static::assertCount(2, $ancestors);
        static::assertContainsOnlyInstancesOf(Entity::class, $ancestors);
        static::assertEquals([11, 10], $ancestors->modelKeys());
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
