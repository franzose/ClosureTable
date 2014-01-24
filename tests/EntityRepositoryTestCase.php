<?php namespace Franzose\ClosureTable\Tests;

use \Franzose\ClosureTable\EntityRepository;
use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Mockery;

class EntityRepositoryTestCase extends BaseTestCase {

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var Mockery\MockInterface|\Yay_MockObject
     */
    protected $entity;

    /**
     * @var array
     */
    protected $relationships = [
        'ancestors', 'descendants', 'children',
        'siblings', 'prevSiblings', 'nextSiblings'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->repository = new EntityRepository($this->entity);
    }

    public function testAll()
    {
        $collection = Mockery::mock('Illuminate\Database\Eloquent\Collection');
        $this->entity->shouldReceive('all')->andReturn($collection);

        $result = $this->repository->all();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result);
    }

    public function testFind()
    {
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');
        $this->entity->shouldReceive('find')->andReturn($entity);

        $result = $this->repository->find(1);

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testFindByAttributes()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $this->entity->shouldReceive('newQuery')->andReturn($builder);

        $builder->shouldReceive('where')
            ->withArgs([Mockery::type('string'), Mockery::any()])
            ->andReturn($builder);

        $builder->shouldReceive('get')->andReturn(Mockery::mock('Illuminate\Database\Eloquent\Collection'));

        $result = $this->repository->findByAttributes(['title' => 'The Title']);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result);
    }

    public function testParent()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->entity->shouldReceive('parent')->once()->andReturn($builder);
        $builder->shouldReceive('first')->once()->andReturn($entity);

        $result = $this->repository->parent();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    protected function buildBasicRelationshipTests($relationship)
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $collection = Mockery::mock('Illuminate\Database\Eloquent\Collection');

        $this->entity->shouldReceive($relationship)->once()->andReturn($builder);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $result = $this->repository->{$relationship}();

        $message = "Asserting {$relationship} relationship...";
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result, $message);
    }

    protected function buildCountRelatedItemsTests($relationship)
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $this->entity->shouldReceive($relationship)->once()->andReturn($builder);
        $builder->shouldReceive('count')->once()->andReturn(0);

        $relationship = ucfirst($relationship);
        $result = $this->repository->{'count'.$relationship}();

        $message = "Asserting count{$relationship} internal type...";
        $this->assertInternalType('int', $result, $message);

        $message = "Asserting count{$relationship} equality...";
        $this->assertEquals(0, $result, $message);
    }

    public function testRelationships()
    {
        foreach($this->relationships as $relationship)
        {
            $this->buildBasicRelationshipTests($relationship);
            $this->buildCountRelatedItemsTests($relationship);
        }
    }

    public function testChildAt()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->entity->shouldReceive('children')->once()->andReturn($builder);
        $builder->shouldReceive('where')->once()->withArgs([
            EntityInterface::POSITION, '=', Mockery::type('int')
        ])->andReturn($builder)
          ->shouldReceive('first')
          ->andReturn($entity);

        $result = $this->repository->childAt(0);

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testFirstChild()
    {
        $entity  = Mockery::mock('Franzose\ClosureTable\Entity');
        $repository = Mockery::mock('Franzose\ClosureTable\EntityRepository[childAt]', [$this->entity]);
        $repository->shouldReceive('childAt')->once()->with(0, ['*'])->andReturn($entity);

        $result = $repository->firstChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testLastChild()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->entity->shouldReceive('children')->once()->andReturn($builder);
        $builder->shouldReceive('orderBy')
            ->once()
            ->withArgs([EntityInterface::POSITION, 'desc'])
            ->andReturn($builder)
            ->shouldReceive('first')
            ->andReturn($entity);

        $result = $this->repository->lastChild();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testMoveTo()
    {
        $this->entity->shouldReceive('moveTo')->once()->withArgs([
            Mockery::type('Franzose\ClosureTable\Entity'), Mockery::type('int')
        ])->andReturn($this->entity);

        $entity = Mockery::mock('Franzose\ClosureTable\Entity');
        $result = $this->repository->moveTo($entity, 0);

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAppendChildrenThrowsException()
    {
        $this->repository->appendChildren('wrong');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveChildrenThrowsException()
    {
        $this->repository->removeChildren('13', 5);
        $this->repository->removeChildren('aaa', 5);
        $this->repository->removeChildren(5, '13');
        $this->repository->removeChildren(5, 'abc');
        $this->repository->removeChildren('aaa', 'bbb');
    }
    
    public function testSiblings()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $collection = Mockery::mock('Illuminate\Database\Eloquent\Collection');

        $this->entity->shouldReceive('siblings')->once()->andReturn($builder);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $result = $this->repository->siblings();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result);
    }

    public function testNeighbors()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $collection = Mockery::mock('Illuminate\Database\Eloquent\Collection');

        $this->entity->shouldReceive('neighbors')->once()->andReturn($builder);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $result = $this->repository->neighbors();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result);
    }

    public function testSiblingAt()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->entity->shouldReceive('siblings')->once()->andReturn($builder);
        $builder->shouldReceive('where')->once()->withArgs([
            EntityInterface::POSITION, '=', Mockery::type('int')
        ])->andReturn($builder)
            ->shouldReceive('first')
            ->andReturn($entity);

        $result = $this->repository->siblingAt(0);

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testFirstSibling()
    {
        $entity  = Mockery::mock('Franzose\ClosureTable\Entity');
        $repository = Mockery::mock('Franzose\ClosureTable\EntityRepository[siblingAt]', [$this->entity]);
        $repository->shouldReceive('siblingAt')->once()->with(0, ['*'])->andReturn($entity);

        $result = $repository->firstSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testLastSibling()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->entity->shouldReceive('siblings')->once()->andReturn($builder);
        $builder->shouldReceive('orderBy')
            ->once()
            ->withArgs([EntityInterface::POSITION, 'desc'])
            ->andReturn($builder)
            ->shouldReceive('first')
            ->andReturn($entity);

        $result = $this->repository->lastSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testPrevSibling()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->entity->shouldReceive('prevSibling')->once()->andReturn($builder);
        $builder->shouldReceive('first')->andReturn($entity);

        $result = $this->repository->prevSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testNextSibling()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $entity = Mockery::mock('Franzose\ClosureTable\Entity');

        $this->entity->shouldReceive('nextSibling')->once()->andReturn($builder);
        $builder->shouldReceive('first')->andReturn($entity);

        $result = $this->repository->nextSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $result);
    }

    public function testRoots()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $collection = Mockery::mock('Illuminate\Database\Eloquent\Collection');

        $this->entity->shouldReceive('roots')->once()->andReturn($builder);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $result = $this->repository->roots();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result);
    }

    public function testTree()
    {
        $builder = Mockery::mock('Franzose\ClosureTable\Extensions\QueryBuilder');
        $collection = Mockery::mock('Illuminate\Database\Eloquent\Collection');

        $this->entity->shouldReceive('tree')->once()->andReturn($builder);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $collection->shouldReceive('toTree')->once()->andReturn($collection);

        $result = $this->repository->tree();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result);
    }

    public function testSave()
    {
        $this->entity->shouldReceive('save')->andReturn(true);

        $result = $this->repository->save();

        $this->assertInternalType('bool', $result);
        $this->assertTrue($result);
    }

    public function testDestroy()
    {
        $this->entity->shouldReceive('getKeyName')->andReturn('foo');
        $this->entity->shouldReceive('getKey')->andReturn(1);
        $this->entity->shouldReceive('where->delete')->andReturn(true);

        $result = $this->repository->destroy();

        $this->assertInternalType('bool', $result);
        $this->assertTrue($result);
    }
} 