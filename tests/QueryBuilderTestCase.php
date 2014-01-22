<?php namespace Franzose\ClosureTable\Tests;

use \Mockery;
use \Illuminate\Database\Query\Grammars\Grammar;
use \Franzose\ClosureTable\Extensions\QueryBuilder;

/**
 * Class QueryBuilderTestCase
 * @package Franzose\ClosureTable\Tests
 */
class QueryBuilderTestCase extends BaseTestCase {

    protected function getBuilder()
    {
        $connection = Mockery::mock('Illuminate\Database\ConnectionInterface');
        $processor  = Mockery::mock('Illuminate\Database\Query\Processors\Processor');

        $qb = new QueryBuilder($connection, new Grammar, $processor, [
            'pk'              => 'entities.id',
            'pkValue'         => 5,
            'position'        => 'position',
            'positionValue'   => 4,
            'closure'         => 'entities_closure',
            'ancestor'        => 'entities_closure.ancestor',
            'ancestorShort'   => 'ancestor',
            'descendant'      => 'entities_closure.descendant',
            'descendantShort' => 'descendant',
            'depth'           => 'entities_closure.depth',
            'depthShort'      => 'depth',
            'depthValue'      => 0
        ]);

        return $qb;
    }

    public function testParent()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."ancestor" = "entities"."id" '.
               'where "entities_closure"."descendant" = ? and "entities_closure"."depth" = ?';

        $this->assertEquals($sql, $qb->from('entities')->parent()->toSql());
        $this->assertEquals([5, 1], $qb->getBindings());
    }

    public function testAncestors()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."ancestor" = "entities"."id" '.
               'where "entities_closure"."descendant" = ? and "entities_closure"."depth" > ?';

        $this->assertEquals($sql, $qb->from('entities')->ancestors()->toSql());
        $this->assertEquals([5, 0], $qb->getBindings());
    }

    public function testDescendants()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."ancestor" = ? and "entities_closure"."depth" > ?';

        $this->assertEquals($sql, $qb->from('entities')->descendants()->toSql());
        $this->assertEquals([5, 0], $qb->getBindings());
    }

    public function testChildren()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."ancestor" = ? and "entities_closure"."depth" = ?';

        $this->assertEquals($sql, $qb->from('entities')->children()->toSql());
        $this->assertEquals([5, 1], $qb->getBindings());
    }

    public function testSiblings()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."depth" = ? and "entities_closure"."descendant" <> ?';

        $this->assertEquals($sql, $qb->from('entities')->siblings()->toSql());
        $this->assertEquals([0, 5], $qb->getBindings());
    }

    public function testNeighbors()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."depth" = ? and "position" in (?, ?)';

        $this->assertEquals($sql, $qb->from('entities')->neighbors()->toSql());
        $this->assertEquals([0, 3, 5], $qb->getBindings());
    }

    public function testPrevSiblings()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."depth" = ? and "position" < ?';

        $this->assertEquals($sql, $qb->from('entities')->prevSiblings()->toSql());
        $this->assertEquals([0, 4], $qb->getBindings());
    }

    public function testPrevSibling()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."depth" = ? and "position" = ?';

        $this->assertEquals($sql, $qb->from('entities')->prevSibling()->toSql());
        $this->assertEquals([0, 4], $qb->getBindings());
    }

    public function testNextSiblings()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."depth" = ? and "position" > ?';

        $this->assertEquals($sql, $qb->from('entities')->nextSiblings()->toSql());
        $this->assertEquals([0, 4], $qb->getBindings());
    }

    public function testNextSibling()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."depth" = ? and "position" = ?';

        $this->assertEquals($sql, $qb->from('entities')->nextSibling()->toSql());
        $this->assertEquals([0, 4], $qb->getBindings());
    }

    public function testRoots()
    {
        $qb  = $this->getBuilder();
        $sql = 'select distinct *, "c"."ancestor" from "entities" '.
               'inner join "entities_closure" as "c" on "c"."ancestor" = "entities"."id" and "c"."descendant" = "entities"."id" '.
               'where (select count(*) from entities_closure '.
                      'where descendant = c.ancestor '.
                      'and depth > 0) = 0';

        $this->assertEquals($sql, $qb->from('entities')->roots()->toSql());
    }

    public function testTree()
    {
        $qb  = $this->getBuilder();
        $sql = 'select distinct *, "c1"."ancestor", "c1"."descendant", "c1"."depth" '.
               'from "entities" '.
               'inner join "entities_closure" as "c1" on "entities"."id" = "c1"."ancestor" '.
               'inner join "entities_closure" as "c2" on "entities"."id" = "c2"."descendant" '.
               'where c1.ancestor = c1.descendant';

        $this->assertEquals($sql, $qb->from('entities')->tree()->toSql());
    }
} 