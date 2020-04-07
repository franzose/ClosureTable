<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;
use Franzose\ClosureTable\Tests\Page;

class ConstructionTests extends BaseTestCase
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

    public function testCreate()
    {
        $entity = new Page(['title' => 'Item 1']);

        static::assertEquals(null, $entity->position);
        static::assertEquals(null, static::readAttribute($entity, 'previousPosition'));
        static::assertEquals(null, $entity->parent_id);
        static::assertEquals(null, static::readAttribute($entity, 'previousParentId'));

        $entity->save();

        static::assertEquals(9, $entity->position);
        static::assertEquals(null, static::readAttribute($entity, 'previousPosition'));
        static::assertEquals(null, $entity->parent_id);
        static::assertEquals(null, static::readAttribute($entity, 'previousParentId'));
    }
}
