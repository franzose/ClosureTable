<?php namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\ClosureTable;

class ClosureTableTestCase extends BaseTestCase {
    /**
     * @var ClosureTable;
     */
    protected $ctable;

    public function setUp()
    {
        parent::setUp();

        $this->ctable = new ClosureTable;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsRootValidatesItsArgument()
    {
        ClosureTable::find(1)->isRoot('wrong');
    }

    public function testIsRoot()
    {
        $this->assertTrue(ClosureTable::find(1)->isRoot());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInsertNodeValidatesItsArguments()
    {
        $this->ctable->insertNode('wrong', 12);
        $this->ctable->insertNode(12, 'wrong');
        $this->ctable->insertNode('wrong', 'wrong');
    }

    public function testInsertNode()
    {
        $this->ctable->insertNode(12, 12);
        $item = ClosureTable::find(12);

        $this->assertNotNull($item);
        $this->assertEquals(12, $item->{ClosureTable::ANCESTOR});
        $this->assertEquals(12, $item->{ClosureTable::DESCENDANT});
        $this->assertEquals(0, $item->{ClosureTable::DEPTH});
    }

    public function testInsertedNodeDepth()
    {
        $this->ctable->insertNode(12, 12);
        $this->ctable->insertNode(12, 13);

        $item = ClosureTable::find(13);

        $this->assertEquals(1, $item->{ClosureTable::DEPTH});
    }

    public function testValidNumberOfRowsInsertedByInsertNode()
    {
        $this->ctable->insertNode(12, 12);
        $this->ctable->insertNode(12, 13);

        $ancestorRows = \DB::table($this->ctable->getTable())
            ->where(ClosureTable::DESCENDANT, '=', 12)
            ->count();

        $descendantRows = \DB::table($this->ctable->getTable())
            ->where(ClosureTable::DESCENDANT, '=', 13)
            ->count();

        $this->assertEquals(1, $ancestorRows);
        $this->assertEquals(2, $descendantRows);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMoveNodeToValidatesItsArgument()
    {
        $this->ctable->moveNodeTo('wrong');
    }

    public function testMoveNodeToAnotherAncestor()
    {
        $item = ClosureTable::find(1);
        $item->moveNodeTo(2);

        $descendantRows = \DB::table($this->ctable->getTable())
            ->where(ClosureTable::DESCENDANT, '=', 1)
            ->count();

        $ancestorRows = \DB::table($this->ctable->getTable())
            ->where(ClosureTable::DESCENDANT, '=', 2)
            ->count();

        $this->assertEquals(1, $ancestorRows);
        $this->assertEquals(2, $descendantRows);
    }

    public function testMoveNodeToDeepNesting()
    {
        $item = ClosureTable::find(1);
        $item->moveNodeTo(2);

        $item = ClosureTable::find(2);
        $item->moveNodeTo(3);

        $item = ClosureTable::find(3);
        $item->moveNodeTo(4);

        $item = ClosureTable::find(4);
        $item->moveNodeTo(5);

        $descendantRows = \DB::table($this->ctable->getTable())
            ->where(ClosureTable::DESCENDANT, '=', 1)
            ->count();

        $ancestorRows = \DB::table($this->ctable->getTable())
            ->where(ClosureTable::DESCENDANT, '=', 2)
            ->count();

        $this->assertEquals(4, $ancestorRows);
        $this->assertEquals(5, $descendantRows);
    }

    public function testMoveNodeToBecomeRoot()
    {
        $item = ClosureTable::find(1);
        $item->moveNodeTo(2);

        $item = ClosureTable::find(2);
        $item->moveNodeTo(3);

        $item = ClosureTable::find(3);
        $item->moveNodeTo(4);

        $item = ClosureTable::find(4);
        $item->moveNodeTo(5);

        $item = ClosureTable::find(1);
        $item->moveNodeTo();

        $this->assertTrue($item->isRoot());
    }

    public function testAncestorQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable().'.'.ClosureTable::ANCESTOR, $this->ctable->getQualifiedAncestorColumn());
    }

    public function testDescendantQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable().'.'.ClosureTable::DESCENDANT, $this->ctable->getQualifiedDescendantColumn());
    }

    public function testDepthQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable().'.'.ClosureTable::DEPTH, $this->ctable->getQualifiedDepthColumn());
    }
} 