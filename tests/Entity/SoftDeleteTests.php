<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

final class SoftDeleteTests extends BaseTestCase
{
    public function testMovingShiftsSoftDeletedSiblings(): void
    {
        Entity::find(5)->delete();

        $entity = Entity::find(9);
        $entity->position = 0;
        $entity->save();

        Entity::withTrashed()->find(5)->restore();

        static::assertModelAttribute('position', [
            9 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
        ]);
    }

    public function testMovingAwayShiftsSoftDeletedSiblings(): void
    {
        Entity::find(5)->delete();

        Entity::find(1)->moveTo(0, Entity::find(9));

        Entity::withTrashed()->find(5)->restore();

        static::assertModelAttribute('position', [
            2 => 0,
            3 => 1,
            4 => 2,
            5 => 3,
            6 => 4,
            7 => 5,
            8 => 6,
            9 => 7,
        ]);
    }

    public function testCreateAppendsAfterSoftDeletedSiblings(): void
    {
        Entity::find(9)->delete();

        $entity = new Entity;
        $entity->save();

        static::assertEquals(9, $entity->position);
    }
}
