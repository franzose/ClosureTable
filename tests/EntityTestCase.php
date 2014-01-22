<?php namespace Franzose\ClosureTable\Tests;

use \Illuminate\Container\Container as App;
use \Mockery;
use Franzose\ClosureTable\Entity;

class EntityTestCase extends BaseTestCase {
    /**
     * @var Entity;
     */
    protected $entity;

    /**
     * @var Mockery\MockInterface|\Yay_MockObject
     */
    protected $ctableMock;

    public function setUp()
    {
        parent::setUp();

        $this->entity = new Entity;
        $this->ctableMock = Mockery::mock('Franzose\ClosureTable\ClosureTable');

        $this->app->instance('Franzose\ClosureTable\Contracts\ClosureTableInterface', $this->ctableMock);

        $this->entity->setClosureTable($this->ctableMock);
    }

    public function testPositionIsFillable()
    {
        $this->assertContains(Entity::POSITION, $this->entity->getFillable());
    }

    public function testPositionDefaultValue()
    {
        $this->assertEquals(0, $this->entity->{Entity::POSITION});
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMoveToThrowsException()
    {
        $this->entity->moveTo($this->entity, 0);
    }

    public function testMoveTo()
    {
        $this->ctableMock->shouldReceive('moveNodeTo')
            ->once()
            ->with(Mockery::type('int'))
            ->andReturn(Mockery::type('bool'));

        $ancestor = Mockery::mock('Franzose\ClosureTable\Entity');
        $ancestor->shouldReceive('getKey')->andReturn(1);

        $result = $this->entity->moveTo($ancestor, 5);
        $this->assertSame($this->entity, $result);
        $this->assertEquals(5, $result->{Entity::POSITION});
    }
} 