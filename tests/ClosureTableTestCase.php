<?php namespace Franzose\ClosureTable\Tests;

use \Franzose\ClosureTable\Contracts\ClosureTableInterface;
use \Franzose\ClosureTable\Models\ClosureTable;

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
        $this->ctable->insertNode(16, 16);
        $item = ClosureTable::where(ClosureTableInterface::DESCENDANT, '=', 16)->first();

        $this->assertNotNull($item);
        $this->assertEquals(16, $item->{ClosureTable::ANCESTOR});
        $this->assertEquals(16, $item->{ClosureTable::DESCENDANT});
        $this->assertEquals(0, $item->{ClosureTable::DEPTH});
    }

    public function testInsertedNodeDepth()
    {
        $this->ctable->insertNode(16, 16);
        $this->ctable->insertNode(13, 16);

        $item = ClosureTable::where(ClosureTableInterface::DESCENDANT, '=', 16)
                    ->where(ClosureTableInterface::ANCESTOR, '=', 13)->first();

        $this->assertNotNull($item);
        $this->assertEquals(1, $item->{ClosureTable::DEPTH});
    }

    public function testValidNumberOfRowsInsertedByInsertNode()
    {
        $this->ctable->insertNode(1, 17);

        $ancestorRows = ClosureTable::where(ClosureTable::DESCENDANT, '=', 1)->count();
        $descendantRows = ClosureTable::where(ClosureTable::DESCENDANT, '=', 17)->count();

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

        $ancestors = ClosureTable::where(ClosureTable::DESCENDANT, '=', 2)->count();
        $descendants = ClosureTable::where(ClosureTable::DESCENDANT, '=', 1)->count();

        $this->assertEquals(1, $ancestors);
        $this->assertEquals(2, $descendants);
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

        $descendantRows = ClosureTable::where(ClosureTable::DESCENDANT, '=', 1)->count();
        $ancestorRows = ClosureTable::where(ClosureTable::DESCENDANT, '=', 2)->count();

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
        $this->assertFalse(ClosureTable::find(3)->isRoot());
    }

    public function testGetActualAttributes()
    {
        $deepest = ClosureTable::where(ClosureTableInterface::ANCESTOR, '=', 1)
                    ->where(ClosureTableInterface::DESCENDANT, '=', 1)
                    ->first();

        $deepest->moveNodeTo(2);

        $item = ClosureTable::where(ClosureTableInterface::ANCESTOR, '=', 2)
                    ->where(ClosureTableInterface::DESCENDANT, '=', 2)
                    ->first();

        $item->moveNodeTo(3);

        $result = $deepest->getActualAttrs();

        $this->assertEquals(3, $result[ClosureTableInterface::ANCESTOR]);
        $this->assertEquals(1, $result[ClosureTableInterface::DESCENDANT]);
        $this->assertEquals(2, $result[ClosureTableInterface::DEPTH]);

        $result = $deepest->getActualAttrs([ClosureTableInterface::ANCESTOR]);

        $this->assertInternalType('string', $result);
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