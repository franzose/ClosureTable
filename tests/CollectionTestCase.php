<?php namespace Franzose\ClosureTable\Tests;

use \Franzose\ClosureTable\Extensions\Collection;
use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Franzose\ClosureTable\Entity;
use \Mockery;

class CollectionTestCase extends BaseTestCase {

    public function testToTree()
    {
        $rootEntity  = new Entity;
        $rootEntity->save();
        $childEntity = with(new Entity)->moveTo(0, $rootEntity);
        $grandEntity = with(new Entity)->moveTo(0, $childEntity);

        $tree  = with(new Collection([$rootEntity, $childEntity, $grandEntity]))->toTree();
        $rootItem = $tree->get(0);

        $this->assertArrayHasKey(EntityInterface::CHILDREN, $rootItem->getRelations());

        $children = $rootItem->getRelation(EntityInterface::CHILDREN);

        $this->assertCount(1, $children);

        $childItem = $children->get(0);

        $this->assertEquals($childEntity->getKey(), $childItem->getKey());
        $this->assertArrayHasKey(EntityInterface::CHILDREN, $childItem->getRelations());

        $grandItems = $childItem->getRelation(EntityInterface::CHILDREN);

        $this->assertCount(1, $grandItems);

        $grandItem = $grandItems->get(0);

        $this->assertEquals($grandEntity->getKey(), $grandItem->getKey());
        $this->assertArrayNotHasKey(EntityInterface::CHILDREN, $grandItem->getRelations());

    }

    public function testHasChildren()
    {
        $collection = new Collection([new Entity, new Entity, new Entity]);
        $collection->get(0)->setRelation(EntityInterface::CHILDREN, new Collection([new Entity, new Entity, new Entity]));

        $this->assertTrue($collection->hasChildren(0));
    }

    public function testGetChildrenOf()
    {
        $collection = new Collection([new Entity, new Entity, new Entity]);
        $collection->get(0)->setRelation(EntityInterface::CHILDREN, new Collection([new Entity, new Entity, new Entity]));

        $children = $collection->getChildrenOf(0);

        $this->assertInstanceOf('Franzose\ClosureTable\Extensions\Collection', $children);
        $this->assertCount(3, $children);
    }
} 