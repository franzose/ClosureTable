<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;
use InvalidArgumentException;

class MovementTests extends BaseTestCase
{
    public function testMoveToThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        $entity = Entity::find(9);

        $entity->moveTo(0, $entity);
    }

    public function testMoveTo()
    {
        $parent = Entity::find(1);
        $child = Entity::find(2);
        $result = $child->moveTo(0, $parent);

        static::assertSame($child, $result);
        static::assertEquals(0, $result->position);
        static::assertEquals(1, $result->parent_id);
        static::assertEquals($parent->getKey(), $result->getParent()->getKey());
    }

    public function testInsertedNodeDepth()
    {
        $entity = Entity::create(['title' => 'abcde']);
        $child = Entity::create(['title' => 'abcde']);
        $child->moveTo(0, $entity);

        $closure = ClosureTable::whereDescendant($child->getKey())
            ->whereAncestor($entity->getKey())->first();

        static::assertNotNull($closure);
        static::assertEquals(1, $closure->depth);
    }

    public function testValidNumberOfRowsInsertedByInsertNode()
    {
        $ancestor = Entity::create(['title' => 'abcde']);
        $descendant = Entity::create(['title' => 'abcde']);
        $descendant->moveTo(0, $ancestor);

        $ancestorId = $ancestor->getKey();
        $descendantId = $descendant->getKey();
        $columns = ['ancestor', 'descendant', 'depth'];
        $ancestorRows = ClosureTable::where('descendant', '=', $ancestorId)->get($columns);
        $descendantRows = ClosureTable::where('descendant', '=', $descendantId)->get($columns);

        static::assertEquals(
            [
                'ancestor' => $ancestorId,
                'descendant' => $ancestorId,
                'depth' => 0
            ],
            $ancestorRows->get(0)->toArray()
        );
        static::assertEquals(
            [
                [
                    'ancestor' => $descendantId,
                    'descendant' => $descendantId,
                    'depth' => 0
                ],
                [
                    'ancestor' => $ancestorId,
                    'descendant' => $descendantId,
                    'depth' => 1
                ],
            ],
            $descendantRows->toArray()
        );
    }

    public function testMoveNodeToAnotherAncestor()
    {
        $descendant = Entity::find(1);
        $descendant->moveTo(0, 2);

        $ancestors = ClosureTable::whereDescendant(2)->count();
        $descendants = ClosureTable::whereDescendant(1)->count();
        static::assertEquals(1, $ancestors);
        static::assertEquals(2, $descendants);
    }

    public function testMoveNodeToDeepNesting()
    {
        $item = Entity::find(1);
        $item->moveTo(0, 2);

        $item = Entity::find(2);
        $item->moveTo(0, 3);

        $item = Entity::find(3);
        $item->moveTo(0, 4);

        $item = Entity::find(4);
        $item->moveTo(0, 5);

        $descendantRows = ClosureTable::whereDescendant(1)->count();
        $ancestorRows = ClosureTable::whereDescendant(2)->count();

        static::assertEquals(4, $ancestorRows);
        static::assertEquals(5, $descendantRows);
    }

    public function testMoveNodeToBecomeRoot()
    {
        $item = Entity::find(1);
        $item->moveTo(0, 2);

        $item = Entity::find(2);
        $item->moveTo(0, 3);

        $item = Entity::find(3);
        $item->moveTo(0, 4);

        $item = Entity::find(4);
        $item->moveTo(0, 5);

        $item = Entity::find(1);
        $item->moveTo(0);

        static::assertEquals(1, ClosureTable::whereDescendant(1)->count());
    }
}
