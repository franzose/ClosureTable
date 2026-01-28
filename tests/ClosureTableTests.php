<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\ClosureTable;
use Franzose\ClosureTable\Entity;

class ClosureTableTests extends BaseTestCase
{
    private ClosureTable $ctable;
    private string $ancestorColumn;
    private string $descendantColumn;
    private string $depthColumn;

    public function setUp(): void
    {
        parent::setUp();

        $this->ctable = new ClosureTable;
        $this->ancestorColumn = $this->ctable->getAncestorColumn();
        $this->descendantColumn = $this->ctable->getDescendantColumn();
        $this->depthColumn = $this->ctable->getDepthColumn();
    }

    public function testAncestorQualifiedKeyName(): void
    {
        static::assertEquals(
            $this->ctable->getTable() . '.' . $this->ancestorColumn,
            $this->ctable->getQualifiedAncestorColumn()
        );
    }

    public function testDescendantQualifiedKeyName(): void
    {
        static::assertEquals(
            $this->ctable->getTable() . '.' . $this->descendantColumn,
            $this->ctable->getQualifiedDescendantColumn()
        );
    }

    public function testDepthQualifiedKeyName(): void
    {
        static::assertEquals(
            $this->ctable->getTable() . '.' . $this->depthColumn,
            $this->ctable->getQualifiedDepthColumn()
        );
    }

    public function testNewNodeShouldBeInsertedIntoClosureTable(): void
    {
        $entity = Entity::create(['title' => 'abcde']);
        $closure = ClosureTable::whereDescendant($entity->getKey())->first();

        static::assertNotNull($closure);
        static::assertEquals($entity->getKey(), $closure->ancestor);
        static::assertEquals(0, $closure->depth);
    }
}
