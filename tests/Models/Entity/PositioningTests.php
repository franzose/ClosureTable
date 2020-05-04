<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

use DB;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;
use Franzose\ClosureTable\Tests\Page;

final class PositioningTests extends BaseTestCase
{
    public function testCreate()
    {
        DB::statement('DELETE FROM entities');
        DB::statement('DELETE FROM entities_closure');

        $entity1 = new Entity;
        $entity1->save();
        static::assertEquals(0, $entity1->position);

        $entity2 = new Entity;
        $entity2->save();
        static::assertEquals(1, $entity2->position);

        static::assertModelAttribute('position', [16 => 0, 17 => 1]);
    }

    public function testSavingLoadedEntityShouldNotTriggerReordering()
    {
        $entity = new Page(['title' => 'Item 1']);
        $entity->save();

        $id = $entity->getKey();
        $parentId = $entity->parent_id;
        $position = $entity->position;

        $sameEntity = Page::find($id);

        // Sibling node that shouldn't move
        static::assertEquals(8, Page::find(9)->position);
        static::assertEquals(
            $position,
            $sameEntity->position,
            'Position should be the same after a load'
        );

        static::assertEquals(
            $parentId,
            $sameEntity->parent_id,
            'Parent should be the same after a load'
        );

        $sameEntity->title = 'New title';
        $sameEntity->save();

        static::assertEquals(
            8,
            Page::find(9)->position,
            'Sibling node should not have been moved'
        );

        static::assertEquals($id, $sameEntity->getKey());
        static::assertEquals($position, $sameEntity->position);
        static::assertEquals($parentId, $sameEntity->parent_id);
    }

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
