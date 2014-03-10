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
            'parentIdShort'   => 'parent_id',
            'position'        => 'position',
            'positionValue'   => 4,
            'closure'         => 'entities_closure',
            'ancestor'        => 'entities_closure.ancestor',
            'ancestorShort'   => 'ancestor',
            'ancestorValue'   => 2,
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

    public function testDescendantsBySubquery()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'where "entities"."id" in '.
               '(select "descendant" from "entities_closure" '.
                'where "ancestor" = ? and "depth" > ?)';

        $this->assertEquals($sql, $qb->from('entities')->descendants(['*'], QueryBuilder::ALL_BUT_SELF, QueryBuilder::BY_WHERE_IN)->toSql());
        $this->assertEquals([5, 0], $qb->getBindings());
    }

    public function testDescendantsWithSelfByJoin()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" on "entities_closure"."descendant" = "entities"."id" '.
               'where "entities_closure"."ancestor" = ? and "entities_closure"."depth" >= ?';

        $this->assertEquals($sql, $qb->from('entities')->descendants(['*'], QueryBuilder::ALL_INC_SELF)->toSql());
        $this->assertEquals([5, 0], $qb->getBindings());
    }

    public function testDescendantsWithSelfBySubquery()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'where "entities"."id" in '.
               '(select "descendant" from "entities_closure" '.
                'where "ancestor" = ? and "depth" >= ?)';

        $this->assertEquals($sql, $qb->from('entities')->descendants(['*'], QueryBuilder::ALL_INC_SELF, QueryBuilder::BY_WHERE_IN)->toSql());
        $this->assertEquals([5, 0], $qb->getBindings());
    }

    public function testChildren()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" where "parent_id" = ?';

        $this->assertEquals($sql, $qb->from('entities')->children()->toSql());
        $this->assertEquals([5], $qb->getBindings());
    }

    public function testSiblings()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" as "c" on "c"."descendant" = "entities"."id" '.
               'where "c"."depth" = ? and "c"."ancestor" = ? and "c"."descendant" <> ? '.
               'and "parent_id" is null';

        $this->assertEquals($sql, $qb->from('entities')->siblings()->toSql());
        $this->assertEquals([0, 2, 5], $qb->getBindings());
    }

    public function testSiblingsBySubquery()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'where "entities"."id" in '.
               '(select "descendant" from "entities_closure" as "c" '.
                'where "c"."depth" = ? and "c"."ancestor" = ? and "c"."descendant" <> ?) '.
                'and "parent_id" is null';

        $this->assertEquals($sql, $qb->from('entities')->siblings(['*'], QueryBuilder::ALL_BUT_SELF, QueryBuilder::BY_WHERE_IN)->toSql());
        $this->assertEquals([0, 2, 5], $qb->getBindings());
    }

    public function testNeighbors()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" as "c" on "c"."descendant" = "entities"."id" '.
               'where "c"."depth" = ? and "c"."ancestor" = ? '.
                'and "parent_id" is null and "position" in (?, ?)';

        $this->assertEquals($sql, $qb->from('entities')->neighbors()->toSql());
        $this->assertEquals([0, 2, 3, 5], $qb->getBindings());
    }

    public function testPrevSiblings()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" as "c" on "c"."descendant" = "entities"."id" '.
               'where "c"."depth" = ? and "c"."ancestor" = ? '.
               'and "parent_id" is null and "position" < ?';

        $this->assertEquals($sql, $qb->from('entities')->prevSiblings()->toSql());
        $this->assertEquals([0, 2, 4], $qb->getBindings());
    }

    public function testPrevSibling()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" as "c" on "c"."descendant" = "entities"."id" '.
               'where "c"."depth" = ? and "c"."ancestor" = ? '.
               'and "parent_id" is null and "position" = ?';

        $this->assertEquals($sql, $qb->from('entities')->prevSibling()->toSql());
        $this->assertEquals([0, 2, 3], $qb->getBindings());
    }

    public function testNextSiblings()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" as "c" on "c"."descendant" = "entities"."id" '.
               'where "c"."depth" = ? and "c"."ancestor" = ? '.
               'and "parent_id" is null and "position" > ?';

        $this->assertEquals($sql, $qb->from('entities')->nextSiblings()->toSql());
        $this->assertEquals([0, 2, 4], $qb->getBindings());
    }

    public function testNextSiblingsBySubquery()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'where "entities"."id" in '.
               '(select "descendant" from "entities_closure" as "c" '.
                'where "c"."depth" = ? and "c"."ancestor" = ?) '.
                'and "parent_id" is null and "position" > ?';

        $this->assertEquals($sql, $qb->from('entities')->nextSiblings(['*'], QueryBuilder::BY_WHERE_IN)->toSql());
        $this->assertEquals([0, 2, 4], $qb->getBindings());
    }

    public function testNextSibling()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" '.
               'inner join "entities_closure" as "c" on "c"."descendant" = "entities"."id" '.
               'where "c"."depth" = ? and "c"."ancestor" = ? '.
               'and "parent_id" is null and "position" = ?';

        $this->assertEquals($sql, $qb->from('entities')->nextSibling()->toSql());
        $this->assertEquals([0, 2, 5], $qb->getBindings());
    }

    public function testSiblingsRange()
    {
        $qb  = $this->getBuilder();
        $sql = 'select "entities"."id", "position" from "entities" '.
               'inner join "entities_closure" as "c" on "c"."descendant" = "entities"."id" '.
               'where "c"."depth" = ? and "c"."descendant" <> ? '.
               'and "parent_id" is null and "position" in (?, ?, ?)';

        $this->assertEquals($sql, $qb->from('entities')->siblingsRange([1, 2, 3])->toSql());
        $this->assertEquals([0, 5, 1, 2, 3], $qb->getBindings());
    }

    public function testSiblingsRangeBySubquery()
    {
        $qb  = $this->getBuilder();
        $sql = 'select "entities"."id", "position" from "entities" '.
               'where "entities"."id" in '.
               '(select "descendant" from "entities_closure" as "c" '.
                'where "c"."depth" = ? and "c"."descendant" <> ?) '.
                'and "parent_id" is null and "position" in (?, ?, ?)';

        $this->assertEquals($sql, $qb->from('entities')->siblingsRange([1, 2, 3], QueryBuilder::BY_WHERE_IN)->toSql());
        $this->assertEquals([0, 5, 1, 2, 3], $qb->getBindings());
    }

    public function testRoots()
    {
        $qb  = $this->getBuilder();
        $sql = 'select * from "entities" where "parent_id" is null';

        $this->assertEquals($sql, $qb->from('entities')->roots()->toSql());
    }
} 