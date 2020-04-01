<?php
namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Models\ClosureTable;

class ClosureTableTestCase extends BaseTestCase
{
    /**
     * @var ClosureTable;
     */
    private $ctable;

    /**
     * @var string
     */
    private $ancestorColumn;

    /**
     * @var string
     */
    private $descendantColumn;

    /**
     * @var string
     */
    private $depthColumn;

    public function setUp()
    {
        parent::setUp();

        $this->ctable = new ClosureTable;
        $this->ancestorColumn = $this->ctable->getAncestorColumn();
        $this->descendantColumn = $this->ctable->getDescendantColumn();
        $this->depthColumn = $this->ctable->getDepthColumn();
    }

    public function testAncestorQualifiedKeyName()
    {
        static::assertEquals(
            $this->ctable->getTable() . '.' . $this->ancestorColumn,
            $this->ctable->getQualifiedAncestorColumn()
        );
    }

    public function testDescendantQualifiedKeyName()
    {
        static::assertEquals(
            $this->ctable->getTable() . '.' . $this->descendantColumn,
            $this->ctable->getQualifiedDescendantColumn()
        );
    }

    public function testDepthQualifiedKeyName()
    {
        static::assertEquals(
            $this->ctable->getTable() . '.' . $this->depthColumn,
            $this->ctable->getQualifiedDepthColumn()
        );
    }
}
