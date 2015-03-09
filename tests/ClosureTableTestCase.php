<?php
namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Models\ClosureTable;

class ClosureTableTestCase extends BaseTestCase
{
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
     * @dataProvider insertNodeProvider
     */
    public function testInsertNodeValidatesItsArguments($ancestorId, $descendantId)
    {
        $this->ctable->insertNode($ancestorId, $descendantId);
    }

    public function insertNodeProvider()
    {
        return [
            ['wrong', 12],
            [12, 'wrong'],
            ['wrong', 'wrong'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMoveNodeToValidatesItsArgument()
    {
        $this->ctable->moveNodeTo('wrong');
    }

    public function testAncestorQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable() . '.' . $this->ancestorColumn, $this->ctable->getQualifiedAncestorColumn());
    }

    public function testDescendantQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable() . '.' . $this->descendantColumn, $this->ctable->getQualifiedDescendantColumn());
    }

    public function testDepthQualifiedKeyName()
    {
        $this->assertEquals($this->ctable->getTable() . '.' . $this->depthColumn, $this->ctable->getQualifiedDepthColumn());
    }
}
