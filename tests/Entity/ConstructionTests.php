<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;
use PHPUnit\Framework\TestCase;

class ConstructionTests extends TestCase
{
    public function testPositionMustBeFillable()
    {
        $entity = new Entity();

        static::assertTrue($entity->isFillable('position'));
    }

    public function testPositionShouldBeCorrect()
    {
        static::assertNull((new Entity())->position);
        static::assertEquals(0, (new Entity(['position' => -1]))->position);

        $entity = new Entity();
        $entity->position = -1;
        static::assertEquals(0, $entity->position);
    }

    public function testEntityShouldUseDefaultClosureTable()
    {
        $entity = new CustomEntity();
        $closure = $entity->getClosureTable();

        static::assertSame(ClosureTable::class, get_class($closure));
        static::assertEquals($entity->getTable() . '_closure', $closure->getTable());
    }

    public function testNewFromBuilder()
    {
        $entity = new Entity([
            'parent_id' => 123,
            'position' => 5
        ]);

        $newEntity = $entity->newFromBuilder([
            'parent_id' => 321,
            'position' => 0
        ]);

        static::assertEquals(321, static::readAttribute($newEntity, 'previousParentId'));
        static::assertEquals(0, static::readAttribute($newEntity, 'previousPosition'));
    }
}
