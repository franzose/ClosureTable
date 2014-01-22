<?php namespace Franzose\ClosureTable;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Franzose\ClosureTable\Extensions\Collection;
use \Franzose\ClosureTable\Extensions\QueryBuilder;
use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Franzose\ClosureTable\Contracts\ClosureTableInterface;

/**
 * Class Entity
 * @package Franzose\ClosureTable
 */
class Entity extends Eloquent implements EntityInterface {

    /**
     * ClosureTable model instance.
     *
     * @var ClosureTable
     */
    protected $closure;

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = true;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->fillable(array_merge($this->getFillable(), array(static::POSITION)));

        if ( ! isset($attributes[static::POSITION]))
        {
            $attributes[static::POSITION] = 0;
        }

        $this->closure = \App::make('Franzose\ClosureTable\Contracts\ClosureTableInterface');

        parent::__construct($attributes);
    }

    /**
     * @param EntityInterface $target
     * @param int $position
     * @return Entity
     * @throws \InvalidArgumentException
     */
    public function moveTo(EntityInterface $target = null, $position)
    {
        if ($this === $target)
        {
            throw new \InvalidArgumentException('Target entity is equal to the sender.');
        }

        $this->{static::POSITION} = $position;
        $targetId = (is_null($target) ?: $target->getKey());

        if ($this->exists)
        {
            $this->closure->moveNodeTo($targetId);
            $this->save();
        }
        else
        {
            $this->save();
            $this->closure->moveNodeTo($targetId);
        }

        return $this;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();
        $grammar = $conn->getQueryGrammar();

        // Here we do a workaround to simplify QueryBuilder tests
        $attrs = [
            'pk' => $this->getQualifiedKeyName(),
            'pkValue' => $this->getKey(),
            'position' => EntityInterface::POSITION,
            'positionValue'   => $this->{EntityInterface::POSITION},
            'closure'         => $this->closure->getTable(),
            'ancestor'        => $this->closure->getQualifiedAncestorColumn(),
            'ancestorShort'   => ClosureTableInterface::ANCESTOR,
            'descendant'      => $this->closure->getQualifiedDescendantColumn(),
            'descendantShort' => ClosureTableInterface::DESCENDANT,
            'depth'           => $this->closure->getQualifiedDepthColumn(),
            'depthShort'      => ClosureTableInterface::DEPTH,
            'depthValue'      => $this->closure->{ClosureTableInterface::DEPTH}
        ];

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor(), $attrs);
    }
}