<?php
namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Models\ClosureTable;
use Illuminate\Database\QueryException;

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

    public function setUp() : void
    {
        parent::setUp();

        $this->ctable = new ClosureTable;
        $this->ancestorColumn = $this->ctable->getAncestorColumn();
        $this->descendantColumn = $this->ctable->getDescendantColumn();
        $this->depthColumn = $this->ctable->getDepthColumn();
    }

    /**
     * @dataProvider insertNodeProvider
     */
    public function testInsertNodeValidatesItsArguments($ancestorId, $descendantId)
    {
        $this->expectException(QueryException::class);

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

    public function testMoveNodeToValidatesItsArgument()
    {
        $this->expectException(QueryException::class);

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
