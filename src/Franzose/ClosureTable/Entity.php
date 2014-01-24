<?php namespace Franzose\ClosureTable;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

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

    public function setClosureTable(ClosureTableInterface $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Indicates whether the model has children.
     *
     * @return bool
     */
    public function isParent()
    {
        return !!$this->children()->count();
    }

    /**
     * Indicates whether the model has no ancestors.
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->closure->isRoot($this->getKey());
    }

    /**
     * Makes the model a child or a root with given position.
     *
     * @param int $position
     * @param EntityInterface $ancestor
     * @return Entity
     * @throws \InvalidArgumentException
     */
    public function moveTo($position, EntityInterface $ancestor = null)
    {
        if ($this === $ancestor)
        {
            throw new \InvalidArgumentException('Target entity is equal to the sender.');
        }

        $self = clone $this;

        $this->{static::POSITION} = $position;

        $this->save([
            'ancestor' => (is_null($ancestor) ? $ancestor : $ancestor->getKey()),
            'self' => $self
        ]);

        return $this;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        $query = $this->newQueryWithDeleted();

        if ($this->fireModelEvent('saving') === false)
        {
            return false;
        }

        if ($this->exists)
        {
            $primaryKey = $this->getKey();
            $this->closure->{ClosureTableInterface::ANCESTOR} = $primaryKey;
            $this->closure->{ClosureTableInterface::DESCENDANT} = $primaryKey;
            $this->closure->{ClosureTableInterface::DEPTH} = 0;

            if (isset($options['ancestor']))
            {
                $this->closure->moveNodeTo($options['ancestor']);
            }

            $saved = $this->performUpdate($query);
        }
        else
        {
            $saved = $this->performInsert($query);

            if ($saved)
            {
                $descendant = $this->getKey();
                $ancestor = (isset($options['ancestor']) ? $options['ancestor'] : $descendant);

                $this->closure->insertNode($ancestor, $descendant);
            }
        }

        if ($saved)
        {
            $this->finishSave($options);

            if (isset($options['self']))
            {
                $this->reorderSiblings($options['self']);
            }
        }

        return $saved;
    }

    protected function reorderSiblings(EntityInterface $unsavedEntity)
    {
        $position = [
            'original' => $unsavedEntity->getOriginal(static::POSITION),
            'current'  => $this->{static::POSITION}
        ];

        $depth = [
            'original' => $unsavedEntity->closure->getRealAttributes([ClosureTableInterface::DEPTH]),
            'current'  => $this->closure->getRealAttributes([ClosureTableInterface::DEPTH])
        ];

        if (   $depth['current'] != $depth['original']
            || $position['current'] != $position['original'])
        {
            $isSQLite = (\DB::getDriverName() == 'sqlite');
            $keyName  = $this->getQualifiedKeyName();
            $siblings = $this->siblings();

            if ($position['current'] > $position['original'])
            {
                $action = 'decrement';
                $range  = range($position['original'], $position['current']);
            }
            else
            {
                $action = 'increment';
                $range  = range($position['current'], $position['original']-1);
            }

            if ($isSQLite)
            {
                $siblingsIds = $siblings->whereIn(static::POSITION, $range)->lists($keyName);
                $siblings = $this->whereIn($keyName, $siblingsIds);
            }
            else
            {
                $siblings->whereIn(static::POSITION, $range);
            }

            $siblings->$action(static::POSITION);

            if ($depth['current'] != $depth['original'])
            {
                if ($isSQLite)
                {
                    $nextSiblingsIds = $unsavedEntity->nextSiblings([$keyName])->get();
                    $nextSiblings = $this->whereIn($keyName, $nextSiblingsIds);
                }
                else
                {
                    $nextSiblings = $unsavedEntity->nextSiblings();
                }

                $nextSiblings->decrement(static::POSITION);
            }
        }
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
            'depthValue'      => $this->closure->getRealAttributes([ClosureTableInterface::DEPTH])
        ];

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor(), $attrs);
    }
}