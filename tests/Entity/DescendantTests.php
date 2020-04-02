<?php

namespace Franzose\ClosureTable\Tests\Entity;

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

    public function testGetDescendants()
    {
        $entity = Entity::find(9);
        $descendants = $entity->getDescendants();

        static::assertInstanceOf(Collection::class, $descendants);
        static::assertCount(6, $descendants);
        $this->assertArrayValuesEquals($descendants->modelKeys(), [10, 11, 12, 13, 14, 15]);
    }

    public function testGetDescendantsWhere()
    {
        $descendants = Entity::find(9)->getDescendantsWhere('position', '=', 1);

        static::assertCount(1, $descendants);
        $this->assertArrayValuesEquals($descendants->modelKeys(), [13]);
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
