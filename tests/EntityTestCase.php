<?php
namespace Franzose\ClosureTable\Tests;

use DB;
use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;

class EntityTestCase extends BaseTestCase
{
    /**
     * Tested entity.
     *
     * @var Entity;
     */
    protected $entity;

    protected static $force_boot = false;

    /**
     * Children relation index.
     *
     * @var string
     */
    protected $childrenRelationIndex;

    public function setUp()
    {
        parent::setUp();

        // TODO: Remove this when Laravel fixes the issue with model booting in tests
        if (self::$force_boot) {
            Entity::boot();
            Page::boot();
        } else {
            self::$force_boot = true;
        }

        $this->entity = new Entity;
        $this->entity->fillable(['title', 'excerpt', 'body', 'position']);

        $this->childrenRelationIndex = $this->entity->getChildrenRelationIndex();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMoveToThrowsException()
    {
        $this->entity->moveTo(0, $this->entity);
    }

    public function testMoveTo()
    {
        $ancestor = Entity::find(1);
        $result = $this->entity->moveTo(0, $ancestor);

        $this->assertSame($this->entity, $result);
        $this->assertEquals(0, $result->position);
        $this->assertEquals(1, $result->parent_id);
        $this->assertEquals($this->entity->getParent()->getKey(), $ancestor->getKey());
    }

    public function testGetParentAfterMovingToAnAncestor()
    {
        $entity = Entity::find(10);
        $entity->moveTo(0, 15);
        $parent = $entity->getParent();

        $this->assertInstanceOf('Franzose\ClosureTable\Models\Entity', $parent);
        $this->assertEquals(15, $parent->getKey());
    }

    public function testInsertNode()
    {
        $entity = Entity::create(['title' => 'abcde']);
        $closure = ClosureTable::whereDescendant($entity->getKey())->first();

        $this->assertNotNull($closure);
        $this->assertEquals($entity->getKey(), $closure->ancestor);
        $this->assertEquals(0, $closure->depth);
    }

    public function testInsertedNodeDepth()
    {
        $entity = Entity::create(['title' => 'abcde']);
        $child = Entity::create(['title' => 'abcde']);
        $child->moveTo(0, $entity);

        $closure = ClosureTable::whereDescendant($child->getKey())
            ->whereAncestor($entity->getKey())->first();

        $this->assertNotNull($closure);
        $this->assertEquals(1, $closure->depth);
    }

    public function testValidNumberOfRowsInsertedByInsertNode()
    {
        $ancestor = Entity::create(['title' => 'abcde']);
        $descendant = Entity::create(['title' => 'abcde']);
        $descendant->moveTo(0, $ancestor);

        $ancestorRows = ClosureTable::whereDescendant($ancestor->getKey())->count();
        $descendantRows = ClosureTable::whereDescendant($descendant->getKey())->count();

        $this->assertEquals(1, $ancestorRows);
        $this->assertEquals(2, $descendantRows);
    }

    public function testMoveNodeToAnotherAncestor()
    {
        $descendant = Entity::find(1);
        $descendant->moveTo(0, 2);

        $ancestors = ClosureTable::whereDescendant(2)->count();
        $descendants = ClosureTable::whereDescendant(1)->count();

        $this->assertEquals(1, $ancestors);
        $this->assertEquals(2, $descendants);
    }

    public function testMoveNodeToDeepNesting()
    {
        $item = Entity::find(1);
        $item->moveTo(0, 2);

        $item = Entity::find(2);
        $item->moveTo(0, 3);

        $item = Entity::find(3);
        $item->moveTo(0, 4);

        $item = Entity::find(4);
        $item->moveTo(0, 5);

        $descendantRows = ClosureTable::whereDescendant(1)->count();
        $ancestorRows = ClosureTable::whereDescendant(2)->count();

        $this->assertEquals(4, $ancestorRows);
        $this->assertEquals(5, $descendantRows);
    }

    public function testMoveNodeToBecomeRoot()
    {
        $item = Entity::find(1);
        $item->moveTo(0, 2);

        $item = Entity::find(2);
        $item->moveTo(0, 3);

        $item = Entity::find(3);
        $item->moveTo(0, 4);

        $item = Entity::find(4);
        $item->moveTo(0, 5);

        $item = Entity::find(1);
        $item->moveTo(0);

        $this->assertEquals(1, ClosureTable::whereDescendant(1)->count());
    }
}
