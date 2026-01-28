<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Entity;
use Franzose\ClosureTable\EntityCollection;
use Franzose\ClosureTable\Tests\BaseTestCase;

class DescendantTests extends BaseTestCase
{
    public function testGetDescendantsShouldReturnAnEmptyCollection(): void
    {
        static::assertCount(0, (new Entity())->getDescendants());
        static::assertCount(0, Entity::find(1)->getDescendants());
    }

    public function testDescendantsScope(): void
    {
        $entity = Entity::find(9);

        $descendants = $entity->descendants()->get();

        static::assertCount(6, $descendants);
        static::assertEquals([10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testDescendantsOfScope(): void
    {
        $descendants = Entity::descendantsOf(9)->get();

        static::assertCount(6, $descendants);
        static::assertEquals([10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testDescendantsWithSelfScope(): void
    {
        $entity = Entity::find(9);

        $descendants = $entity->descendantsWithSelf()->get();

        static::assertCount(7, $descendants);
        static::assertEquals([9, 10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testDescendantsWithSelfOfScope(): void
    {
        $descendants = Entity::descendantsWithSelfOf(9)->get();

        static::assertCount(7, $descendants);
        static::assertEquals([9, 10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testGetDescendants(): void
    {
        $entity = Entity::find(9);
        $descendants = $entity->getDescendants();

        static::assertInstanceOf(EntityCollection::class, $descendants);
        static::assertCount(6, $descendants);
        static::assertEquals([10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testGetDescendantsWhere(): void
    {
        $descendants = Entity::find(9)->descendants()->where('position', '=', 1)->get();

        static::assertCount(1, $descendants);
        static::assertEquals([13], $descendants->modelKeys());
    }

    public function testCountDescendants(): void
    {
        static::assertEquals(6, Entity::find(9)->countDescendants());
        static::assertEquals(0, Entity::find(1)->countDescendants());
    }

    public function testHasDescendants(): void
    {
        static::assertTrue(Entity::find(9)->hasDescendants());
        static::assertFalse(Entity::find(1)->hasDescendants());
    }
}
