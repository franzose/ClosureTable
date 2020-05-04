<?php
namespace Franzose\ClosureTable\Tests\Models;

use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

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

    public function testNewNodeShouldBeInsertedIntoClosureTable()
    {
        $entity = Entity::create(['title' => 'abcde']);
        $closure = ClosureTable::whereDescendant($entity->getKey())->first();

        static::assertNotNull($closure);
        static::assertEquals($entity->getKey(), $closure->ancestor);
        static::assertEquals(0, $closure->depth);
    }
}
