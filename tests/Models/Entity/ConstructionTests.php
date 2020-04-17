<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

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

    public function testCreate()
    {
        $entity = new Page(['title' => 'Item 1']);

        static::assertEquals(null, $entity->position);
        static::assertEquals(null, $entity->parent_id);

        $entity->save();

        static::assertEquals(9, $entity->position);
        static::assertEquals(null, $entity->parent_id);
    }
}
