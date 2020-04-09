<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

class DescendantTests extends BaseTestCase
{
    public function testGetDescendantsShouldReturnAnEmptyCollection()
    {
        static::assertCount(0, (new Entity())->getDescendants());
        static::assertCount(0, Entity::find(1)->getDescendants());
    }

    public function testDescendantsScope()
    {
        $entity = Entity::find(9);

        $descendants = $entity->descendants()->get();

        static::assertCount(6, $descendants);
        static::assertEquals([10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testDescendantsOfScope()
    {
        $descendants = Entity::descendantsOf(9)->get();

        static::assertCount(6, $descendants);
        static::assertEquals([10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testDescendantsWithSelfScope()
    {
        $entity = Entity::find(9);

        $descendants = $entity->descendantsWithSelf()->get();

        static::assertCount(7, $descendants);
        static::assertEquals([9, 10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testDescendantsWithSelfOfScope()
    {
        $descendants = Entity::descendantsWithSelfOf(9)->get();

        static::assertCount(7, $descendants);
        static::assertEquals([9, 10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testGetDescendants()
    {
        $entity = Entity::find(9);
        $descendants = $entity->getDescendants();

        static::assertInstanceOf(Collection::class, $descendants);
        static::assertCount(6, $descendants);
        static::assertEquals([10, 11, 12, 13, 14, 15], $descendants->modelKeys());
    }

    public function testGetDescendantsWhere()
    {
        $descendants = Entity::find(9)->getDescendantsWhere('position', '=', 1);

        static::assertCount(1, $descendants);
        static::assertEquals([13], $descendants->modelKeys());
    }

    public function testCountDescendants()
    {
        static::assertEquals(6, Entity::find(9)->countDescendants());
        static::assertEquals(0, Entity::find(1)->countDescendants());
    }

    public function testHasDescendants()
    {
        static::assertTrue(Entity::find(9)->hasDescendants());
        static::assertFalse(Entity::find(1)->hasDescendants());
    }
}
