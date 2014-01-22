<?php namespace Franzose\ClosureTable\Tests;

use \Franzose\ClosureTable\Extensions\Collection;
use \Franzose\ClosureTable\Entity;
use \Mockery;

class CollectionTestCase extends BaseTestCase {

    public function testToTree()
    {
        $rootEntity  = new Entity;
        $rootEntity->save();
        $childEntity = with(new Entity)->moveTo($rootEntity, 0);
        $grandEntity = with(new Entity)->moveTo($childEntity, 0);

        $tree  = with(new Collection([$rootEntity, $childEntity, $grandEntity]))->toTree();
        $rootItem = $tree->get(0);

        $this->assertArrayHasKey('children', $rootItem->getRelations());

        $children = $rootItem->getRelation('children');

        $this->assertCount(1, $children);

        $childItem = $children->get(0);

        $this->assertEquals($childEntity->getKey(), $childItem->getKey());
        $this->assertArrayHasKey('children', $childItem->getRelations());

        $grandItems = $childItem->getRelation('children');

        $this->assertCount(1, $grandItems);

        $grandItem = $grandItems->get(0);

        $this->assertEquals($grandEntity->getKey(), $grandItem->getKey());
        $this->assertArrayNotHasKey('children', $grandItem->getRelations());

    }
} 