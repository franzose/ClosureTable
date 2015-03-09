<?php
namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\Entity;
use Mockery;

class CollectionTestCase extends BaseTestCase
{
    public function testToTree()
    {
        $rootEntity = new Entity;
        $rootEntity->save();
        $childEntity = with(new Entity)->moveTo(0, $rootEntity);
        $grandEntity = with(new Entity)->moveTo(0, $childEntity);

        $childrenRelationIndex = $rootEntity->getChildrenRelationIndex();

        $tree = with(new Collection([$rootEntity, $childEntity, $grandEntity]))->toTree();
        $rootItem = $tree->get(0);

        $this->assertArrayHasKey($childrenRelationIndex, $rootItem->getRelations());

        $children = $rootItem->getRelation($childrenRelationIndex);

        $this->assertCount(1, $children);

        $childItem = $children->get(0);

        $this->assertEquals($childEntity->getKey(), $childItem->getKey());
        $this->assertArrayHasKey($childrenRelationIndex, $childItem->getRelations());

        $grandItems = $childItem->getRelation($childrenRelationIndex);

        $this->assertCount(1, $grandItems);

        $grandItem = $grandItems->get(0);

        $this->assertEquals($grandEntity->getKey(), $grandItem->getKey());
        $this->assertArrayNotHasKey($childrenRelationIndex, $grandItem->getRelations());
    }

    public function testHasChildren()
    {
        $entity = new Entity;
        $childrenRelationIndex = $entity->getChildrenRelationIndex();

        $collection = new Collection([$entity, new Entity, new Entity]);
        $collection->get(0)->setRelation($childrenRelationIndex, new Collection([new Entity, new Entity, new Entity]));

        $this->assertTrue($collection->hasChildren(0));
    }

    public function testGetChildrenOf()
    {
        $entity = new Entity;
        $childrenRelationIndex = $entity->getChildrenRelationIndex();

        $collection = new Collection([$entity, new Entity, new Entity]);
        $collection->get(0)->setRelation($childrenRelationIndex, new Collection([new Entity, new Entity, new Entity]));

        $children = $collection->getChildrenOf(0);

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $children);
        $this->assertCount(3, $children);
    }
}
