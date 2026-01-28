<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\ClosureTable;
use Franzose\ClosureTable\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;
use Franzose\ClosureTable\Tests\Page;

class ConstructionTests extends BaseTestCase
{
    public function testPositionMustBeFillable(): void
    {
        $entity = new Entity();

        static::assertTrue($entity->isFillable('position'));
    }

    public function testPositionShouldBeCorrect(): void
    {
        static::assertNull((new Entity())->position);
        static::assertEquals(0, (new Entity(['position' => -1]))->position);

        $entity = new Entity();
        $entity->position = -1;
        static::assertEquals(0, $entity->position);
    }

    public function testEntityShouldUseDefaultClosureTable(): void
    {
        $entity = new CustomEntity();
        $closure = $entity->getClosureTable();

        static::assertSame(ClosureTable::class, get_class($closure));
        static::assertEquals($entity->getTable() . '_closure', $closure->getTable());
    }

    public function testNewFromBuilderUsesProvidedConnection(): void
    {
        $entity = (new Entity())->newFromBuilder(['id' => 1], 'sqlite');

        static::assertEquals('sqlite', $entity->getConnectionName());
    }

    public function testCreate(): void
    {
        $entity = new Page(['title' => 'Item 1']);

        static::assertEquals(null, $entity->position);
        static::assertEquals(null, $entity->parent_id);

        $entity->save();

        static::assertEquals(9, $entity->position);
        static::assertEquals(null, $entity->parent_id);
    }

    public function testDeleteUsesSoftDeletes(): void
    {
        $entity = Entity::create(['title' => 'Item 1']);

        $entity->delete();

        static::assertNull(Entity::find($entity->getKey()));
        static::assertNotNull(Entity::withTrashed()->find($entity->getKey())->deleted_at);
    }
}
