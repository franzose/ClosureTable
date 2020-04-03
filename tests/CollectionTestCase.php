<?php
namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\Entity;

class CollectionTestCase extends BaseTestCase
{
    public function testGetChildAt()
    {
        $collection = new Collection([
            new Page(['position' => 0]),
            new Page(['position' => 1]),
            new Page(['position' => 2]),
        ]);

        static::assertEquals(1, $collection->getChildAt(1)->position);
        static::assertNull($collection->getChildAt(999));
    }

    public function testGetFirstChild()
    {
        $collection = new Collection([
            new Page(['position' => 0]),
            new Page(['position' => 1]),
        ]);

        static::assertEquals(0, $collection->getFirstChild()->position);
        static::assertNull((new Collection())->getFirstChild());
    }

    public function testGetLastChild()
    {
        $collection = new Collection([
            new Page(['position' => 0]),
            new Page(['position' => 1]),
        ]);

        static::assertEquals(1, $collection->getLastChild()->position);
        static::assertNull((new Collection())->getLastChild());
    }

    public function testGetRange()
    {
        $collection = new Collection([
            new Page(['position' => 0]),
            new Page(['position' => 1]),
            new Page(['position' => 2]),
            new Page(['position' => 3]),
        ]);

        static::assertEquals([2, 3], $collection->getRange(2)->pluck('position')->toArray());
        static::assertEquals([1, 2, 3], $collection->getRange(1, 3)->pluck('position')->toArray());
    }

    public function testGetChildrenOf()
    {
        $entity = new Page(['position' => 0]);
        $childrenRelationIndex = $entity->getChildrenRelationIndex();

        $collection = new Collection([
            $entity,
            new Page(['position' => 1]),
            new Page(['position' => 2])
        ]);

        $expected = new Collection([
            new Page(['position' => 0]),
            new Page(['position' => 1]),
            new Page(['position' => 2])
        ]);

        /** @var Entity $firstEntity */
        $firstEntity = $collection->get(0);
        $firstEntity->setRelation($childrenRelationIndex, $expected);

        $actual = $collection->getChildrenOf(0);

        static::assertSame($expected, $actual);
    }

    public function testHasChildren()
    {
        $entity = new Page(['position' => 0]);
        $childrenRelationIndex = $entity->getChildrenRelationIndex();

        $collection = new Collection([
            $entity,
            new Page(['position' => 1]),
            new Page(['position' => 2])
        ]);

        $children = new Collection([
            new Page(['position' => 0]),
            new Page(['position' => 1]),
            new Page(['position' => 2])
        ]);

        /** @var Entity $firstEntity */
        $firstEntity = $collection->get(0);
        $firstEntity->setRelation($childrenRelationIndex, $children);

        static::assertTrue($collection->hasChildren(0));
    }

    public function testToTree()
    {
        $root = new Page(['id' => 1]);
        $child = new Page(['id' => 2, 'parent_id' => 1]);
        $grandChild = new Page(['id' => 3, 'parent_id' => 2]);

        $tree = (new Collection([$root, $child, $grandChild]))->toTree();

        static::assertCount(1, $tree);

        $children = $tree->get(0)->children;
        static::assertCount(1, $children);
        static::assertSame($child, $children->get(0));

        $grandChildren = $children->get(0)->children;
        static::assertCount(1, $grandChildren);
        static::assertSame($grandChild, $grandChildren->get(0));
    }
}
