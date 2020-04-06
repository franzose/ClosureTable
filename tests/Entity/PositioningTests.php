<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

final class PositioningTests extends BaseTestCase
{
    public function testMoveToTheFirstPosition()
    {
        $entity = Entity::find(9);

        $entity->position = 0;
        $entity->save();

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

    public function testMoveToTheFifthPosition()
    {
        $entity = Entity::find(9);

        $entity->position = 5;
        $entity->save();

        static::assertModelAttribute('position', [
            1 => 0,
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            9 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
        ]);
    }

    public function testMoveToPositionWhichIsOutOfTheUpperBound()
    {
        $entity = Entity::find(1);

        $entity->position = 999;
        $entity->save();

        static::assertModelAttribute('position', [
            1 => 8, //
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
}
