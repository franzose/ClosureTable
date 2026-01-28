<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Entity;
use Franzose\ClosureTable\EntityCollection;
use Franzose\ClosureTable\Tests\BaseTestCase;

class AncestorTests extends BaseTestCase
{
    public function testGetAncestorsShouldReturnAnEmptyCollection(): void
    {
        static::assertCount(0, (new Entity())->getAncestors());
        static::assertCount(0, Entity::find(1)->getAncestors());
    }

    public function testAncestorsScope(): void
    {
        $entity = Entity::find(12);

        $ancestors = $entity->ancestors()->get();

        static::assertCount(3, $ancestors);
        static::assertEquals([11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsOfScope(): void
    {
        $ancestors = Entity::ancestorsOf(12)->get();

        static::assertCount(3, $ancestors);
        static::assertEquals([11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsWithSelfScope(): void
    {
        $entity = Entity::find(12);

        $ancestors = $entity->ancestorsWithSelf()->get();

        static::assertCount(4, $ancestors);
        static::assertEquals([12, 11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsWithSelfOfScope(): void
    {
        $ancestors = Entity::ancestorsWithSelfOf(12)->get();

        static::assertCount(4, $ancestors);
        static::assertEquals([12, 11, 10, 9], $ancestors->modelKeys());
    }

    public function testGetAncestorsShouldNotBeEmpty(): void
    {
        $entity = Entity::find(12);

        $ancestors = $entity->getAncestors();

        static::assertInstanceOf(EntityCollection::class, $ancestors);
        static::assertCount(3, $ancestors);
        static::assertContainsOnlyInstancesOf(Entity::class, $ancestors);
        static::assertEquals([11, 10, 9], $ancestors->modelKeys());
    }

    public function testAncestorsWhere(): void
    {
        $entity = Entity::find(12);

        $ancestors = $entity->getAncestorsWhere('position', '<', 2);

        static::assertInstanceOf(EntityCollection::class, $ancestors);
        static::assertCount(2, $ancestors);
        static::assertContainsOnlyInstancesOf(Entity::class, $ancestors);
        static::assertEquals([11, 10], $ancestors->modelKeys());
    }

    public function testCountAncestors(): void
    {
        static::assertEquals(0, Entity::find(1)->countAncestors());
        static::assertEquals(3, Entity::find(12)->countAncestors());
    }

    public function testHasAncestors(): void
    {
        static::assertFalse(Entity::find(1)->hasAncestors());
        static::assertTrue(Entity::find(12)->hasAncestors());
    }
}
