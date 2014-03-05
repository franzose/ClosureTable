<?php namespace Franzose\ClosureTable\Tests;

use \Franzose\ClosureTable\Models\ClosureTable;

class ClosureTableTestCase extends BaseTestCase {
    /**
     * @var ClosureTable;
     */
    protected $ctable;

    /**
     * @var string
     */
    protected $ancestorColumn;

    /**
     * @var string
     */
    protected $descendantColumn;

    /**
     * @var string
     */
    protected $depthColumn;

    public function setUp()
    {
        parent::setUp();

        $this->ctable = new ClosureTable;
        $this->ancestorColumn = $this->ctable->getAncestorColumn();
        $this->descendantColumn = $this->ctable->getDescendantColumn();
        $this->depthColumn = $this->ctable->getDepthColumn();
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
        $item = ClosureTable::where($this->descendantColumn, '=', 16)->first();

        $this->assertNotNull($item);
        $this->assertEquals(16, $item->{$this->ancestorColumn});
        $this->assertEquals(16, $item->{$this->descendantColumn});
        $this->assertEquals(0, $item->{$this->depthColumn});
    }

    public function testInsertedNodeDepth()
    {
        $this->ctable->insertNode(20, 20);
        $this->ctable->insertNode(13, 20);

        $item = ClosureTable::where($this->descendantColumn, '=', 20)
                    ->where($this->ancestorColumn, '=', 13)->first();

        $this->assertNotNull($item);
        $this->assertEquals(1, $item->{$this->depthColumn});
    }

    public function testValidNumberOfRowsInsertedByInsertNode()
    {
        $this->ctable->insertNode(1, 17);

        $ancestorRows = ClosureTable::where($this->descendantColumn, '=', 1)->count();
        $descendantRows = ClosureTable::where($this->descendantColumn, '=', 17)->count();

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

        $ancestors = ClosureTable::where($this->descendantColumn, '=', 2)->count();
        $descendants = ClosureTable::where($this->descendantColumn, '=', 1)->count();

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

        $descendantRows = ClosureTable::where($this->descendantColumn, '=', 1)->count();
        $ancestorRows = ClosureTable::where($this->descendantColumn, '=', 2)->count();

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
        $deepest = ClosureTable::where($this->ancestorColumn, '=', 1)
                    ->where($this->descendantColumn, '=', 1)
                    ->first();

        $deepest->moveNodeTo(2);

        $item = ClosureTable::where($this->ancestorColumn, '=', 2)
                    ->where($this->descendantColumn, '=', 2)
                    ->first();

        $item->moveNodeTo(3);

        $result = $deepest->getActualAttrs();

        $this->assertEquals(3, $result[$this->ancestorColumn]);
        $this->assertEquals(1, $result[$this->descendantColumn]);
        $this->assertEquals(2, $result[$this->depthColumn]);

        $result = $deepest->getActualAttrs([$this->ancestorColumn]);

        $this->assertInternalType('string', $result);
    }

    public function testAncestorQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable().'.'.$this->ancestorColumn, $this->ctable->getQualifiedAncestorColumn());
    }

    public function testDescendantQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable().'.'.$this->descendantColumn, $this->ctable->getQualifiedDescendantColumn());
    }

    public function testDepthQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable().'.'.$this->depthColumn, $this->ctable->getQualifiedDepthColumn());
    }
} 