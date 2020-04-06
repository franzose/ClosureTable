<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;
use PHPUnit\Framework\TestCase;

class ConstructionTests extends TestCase
{
    public function testPositionAndRealDepthColumnsMustBeFillable()
    {
        $entity = new Entity();

        static::assertTrue($entity->isFillable('position'));
        static::assertTrue($entity->isFillable('real_depth'));
    }

    public function testPositionShouldBeCorrect()
    {
        static::assertNull((new Entity())->position);
        static::assertEquals(0, (new Entity(['position' => -1]))->position);
    }

    public function testRealDepthShouldBeSetToZero()
    {
        static::assertEquals(0, (new Entity())->real_depth);
        static::assertEquals(0, (new Entity(['real_depth' => null]))->real_depth);
        static::assertEquals(0, (new Entity(['real_depth' => -1]))->real_depth);
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
            'position' => 5,
            'real_depth' => 2
        ]);

        $newEntity = $entity->newFromBuilder([
            'parent_id' => 321,
            'position' => 0,
            'real_depth' => 0
        ]);

        static::assertEquals(321, static::readAttribute($newEntity, 'previousParentId'));
        static::assertEquals(0, static::readAttribute($newEntity, 'previousPosition'));
        static::assertEquals(0, static::readAttribute($newEntity, 'previousRealDepth'));
    }

    public function testSetPositionAttribute()
    {
        $entity = new Entity();

        $entity->position = -1;

        static::assertEquals(0, $entity->position);
    }
}
