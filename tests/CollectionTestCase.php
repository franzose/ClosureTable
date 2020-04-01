<?php
namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\Entity;

class CollectionTestCase extends BaseTestCase
{
    public function testToTree()
    {
        $rootEntity = new Entity();
        $rootEntity->save();
        $childEntity = (new Entity())->moveTo(0, $rootEntity);
        $grandEntity = (new Entity())->moveTo(0, $childEntity);

        $childrenRelationIndex = $rootEntity->getChildrenRelationIndex();

        $tree = (new Collection([$rootEntity, $childEntity, $grandEntity]))->toTree();

        /** @var Entity $rootItem */
        $rootItem = $tree->get(0);

        static::assertArrayHasKey($childrenRelationIndex, $rootItem->getRelations());

        /** @var Collection $children */
        $children = $rootItem->getRelation($childrenRelationIndex);

        static::assertCount(1, $children);

        /** @var Entity $childItem */
        $childItem = $children->get(0);

        static::assertEquals($childEntity->getKey(), $childItem->getKey());
        static::assertArrayHasKey($childrenRelationIndex, $childItem->getRelations());

        /** @var Collection $grandItems */
        $grandItems = $childItem->getRelation($childrenRelationIndex);

        static::assertCount(1, $grandItems);

        /** @var Entity $grandItem */
        $grandItem = $grandItems->get(0);

        static::assertEquals($grandEntity->getKey(), $grandItem->getKey());
        static::assertArrayNotHasKey($childrenRelationIndex, $grandItem->getRelations());
    }

    public function testHasChildren()
    {
        $entity = new Entity();
        $childrenRelationIndex = $entity->getChildrenRelationIndex();

        $collection = new Collection([
            $entity,
            new Entity(),
            new Entity()
        ]);

        $children = new Collection([
            new Entity(),
            new Entity(),
            new Entity()
        ]);

        /** @var Entity $firstEntity */
        $firstEntity = $collection->get(0);
        $firstEntity->setRelation($childrenRelationIndex, $children);

        static::assertTrue($collection->hasChildren(0));
    }

    public function testGetChildrenOf()
    {
        $entity = new Entity();
        $childrenRelationIndex = $entity->getChildrenRelationIndex();

        $collection = new Collection([
            $entity,
            new Entity(),
            new Entity()
        ]);

        $expected = new Collection([
            new Entity(),
            new Entity(),
            new Entity()
        ]);

        /** @var Entity $firstEntity */
        $firstEntity = $collection->get(0);
        $firstEntity->setRelation($childrenRelationIndex, $expected);

        $actual = $collection->getChildrenOf(0);

        static::assertSame($expected, $actual);
    }
}
