<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

class DepthTests extends BaseTestCase
{
    public function testDepthAccessorUsesEagerLoadedRelation(): void
    {
        $expected = [
            9 => 0,
            10 => 1,
            11 => 2,
            12 => 3,
            13 => 1,
            14 => 1,
            15 => 1,
        ];

        $entities = Entity::whereIn('id', array_keys($expected))->get();

        foreach ($expected as $id => $depth) {
            $entity = $entities->firstWhere('id', $id);

            static::assertNotNull($entity);
            static::assertTrue($entity->relationLoaded('closureDepth'));
            static::assertEquals($depth, $entity->depth);
        }
    }

    public function testDepthUpdatesAfterMove(): void
    {
        $entity = Entity::find(12);
        $entity->moveTo(0, 13);

        $entity = Entity::find(12);

        static::assertEquals(2, $entity->depth);
    }

    public function testDepthForCreatedEntityWithoutRelationLoaded(): void
    {
        $entity = Entity::create(['title' => 'abcde']);

        static::assertFalse($entity->relationLoaded('closureDepth'));
        static::assertEquals(0, $entity->depth);
    }

    public function testDepthForUnsavedEntityIsNull(): void
    {
        static::assertNull((new Entity())->depth);
    }
}
