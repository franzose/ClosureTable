<?php namespace Franzose\ClosureTable\Tests;

use \Franzose\ClosureTable\Extensions\Collection;
use \Mockery;

class CollectionTestCase extends BaseTestCase {

    public function testToTree()
    {
        $grandchild = Mockery::mock('Franzose\ClosureTable\Entity');
        $child      = Mockery::mock('Franzose\ClosureTable\Entity');
        $parent     = Mockery::mock('Franzose\ClosureTable\Entity');

        $gcqb = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $grandchild->shouldReceive('parent')->andReturn($gcqb);
        $gcqb->shouldReceive('first')->andReturn($child);

        $cqb = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $child->shouldReceive('parent')->andReturn($cqb);
        $cqb->shouldReceive('first')->andReturn($parent);

        $collection = new Collection([$grandchild, $child, $parent]);
    }
} 