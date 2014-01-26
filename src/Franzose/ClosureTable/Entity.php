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

        $this->makeClosureTable();

        parent::__construct($attributes);
    }

    /**
     * Makes closure table.
     */
    protected function makeClosureTable()
    {
        $this->closure = new ClosureTable;
    }

    /**
     * //
     */
    protected function initClosureTable()
    {
        if (is_null($this->closure->{ClosureTableInterface::ANCESTOR}))
        {
            $primaryKey = $this->getKey();
            $this->closure->{ClosureTableInterface::ANCESTOR} = $primaryKey;
            $this->closure->{ClosureTableInterface::DESCENDANT} = $primaryKey;
            $this->closure->{ClosureTableInterface::DEPTH} = 0;
        }
    }

    /**
     * Sets closure table.
     *
     * @param ClosureTableInterface $closure
     */
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
     * @param array $columns
     * @return Entity
     */
    public function getParent(array $columns = ['*'])
    {
        return $this->parent($columns)->first();
    }

    /**
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getAncestors(array $columns = ['*'])
    {
        return $this->ancestors($columns)->get();
    }

    /**
     * @return int
     */
    public function countAncestors()
    {
        return (int)$this->ancestors()->count();
    }

    /**
     * @return bool
     */
    public function hasAncestors()
    {
        return !!$this->countAncestors();
    }

    /**
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getDescendants(array $columns = ['*'])
    {
        return $this->descendants($columns)->get();
    }

    /**
     * @return int
     */
    public function countDescendants()
    {
        return (int)$this->descendants()->count();
    }

    /**
     * @return bool
     */
    public function hasDescendants()
    {
        return !!$this->countDescendants();
    }

    /**
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getChildren(array $columns = ['*'])
    {
        if ($this->hasChildrenRelation())
        {
            $result = $this->getRelation('children');
        }
        else
        {
            $result = $this->children($columns)->get();
        }

        return $result;
    }

    /**
     * @return int
     */
    public function countChildren()
    {
        if ($this->hasChildrenRelation())
        {
            $result = $this->getRelation('children')->count();
        }
        else
        {
            $result = $this->children()->count();
        }

        return (int)$result;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !!$this->countChildren();
    }

    /**
     * @return bool
     */
    protected function hasChildrenRelation()
    {
        return array_key_exists('children', $this->getRelations());
    }

    /**
     * @param $position
     * @param array $columns
     * @return Entity
     */
    public function getChildAt($position, array $columns = ['*'])
    {
        return $this->childAt($position, $columns)->first();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function getFirstChild(array $columns = ['*'])
    {
        return $this->getChildAt(0, $columns);
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function getLastChild(array $columns = ['*'])
    {
        return $this->children($columns)->orderBy(EntityInterface::POSITION, 'desc')->first();
    }

    /**
     * @param EntityInterface $child
     * @param int $position
     * @return Entity
     */
    public function appendChild(EntityInterface $child, $position = null)
    {
        $child->moveTo($position, $this);

        return $this;
    }

    /**
     * @param array|\Illuminate\Database\Eloquent\Collection $children
     * @return Entity
     * @throws \InvalidArgumentException
     */
    public function appendChildren($children)
    {
        if ( ! is_array($children) && ! $children instanceof \Illuminate\Database\Eloquent\Collection)
        {
            throw new \InvalidArgumentException('Children argument must be of type array or \Illuminate\Database\Eloquent\Collection.');
        }

        \DB::transaction(function() use($children)
        {
            $lastChildPosition = $this->getLastChild([EntityInterface::POSITION]);

            if (is_null($lastChildPosition))
            {
                $lastChildPosition = 0;
            }

            foreach($children as $child)
            {
                if ( ! $child instanceof EntityInterface)
                {
                    throw new \InvalidArgumentException('Array items must be of type EntityInterface.');
                }

                $this->appendChild($child, $lastChildPosition);
                $lastChildPosition++;
            }
        });

        return $this;
    }

    /**
     * @param int $position
     * @param bool $forceDelete
     * @return Entity
     */
    public function removeChild($position = null, $forceDelete = false)
    {
        $action = ($forceDelete === true ? 'forceDelete' : 'delete');
        $child  = $this->getChildAt($position);

        if ( ! is_null($child))
        {
            $child->$action();
        }

        return $this;
    }

    /**
     * @param int $from
     * @param null $to
     * @param bool $forceDelete
     * @return Entity
     * @throws \InvalidArgumentException
     */
    public function removeChildren($from, $to = null, $forceDelete = false)
    {
        if ( ! is_int($from) || ( ! is_null($to) && ! is_int($to)))
        {
            throw new \InvalidArgumentException('`from` and `to` are the position boundaries. They must be of type int.');
        }

        if (is_null($to))
        {
            $to = $this->getLastChild([EntityInterface::POSITION])->{EntityInterface::POSITION};
        }

        foreach(range($from, $to) as $position)
        {
            $this->removeChild($position, $forceDelete);
        }

        return $this;
    }

    /**
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getSiblings(array $columns = ['*'])
    {
        return $this->siblings($columns)->get();
    }

    /**
     * @return int
     */
    public function countSiblings()
    {
        return $this->siblings()->count();
    }

    /**
     * @return bool
     */
    public function hasSiblings()
    {
        return !!$this->countSiblings();
    }

    /**
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getNeighbors(array $columns = ['*'])
    {
        return $this->neighbors($columns)->get();
    }

    /**
     * @param int $position
     * @param array $columns
     * @return Entity
     */
    public function getSiblingAt($position, array $columns = ['*'])
    {
        return $this->siblingAt($position, $columns)->first();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function getFirstSibling(array $columns = ['*'])
    {
        return $this->getSiblingAt(0, $columns);
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function getLastSibling(array $columns = ['*'])
    {
        return $this->siblings($columns)->orderBy(EntityInterface::POSITION, 'desc')->first();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function getPrevSibling(array $columns = ['*'])
    {
        return $this->prevSibling($columns)->first();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getPrevSiblings(array $columns = ['*'])
    {
        return $this->prevSiblings($columns)->get();
    }

    /**
     * @return int
     */
    public function countPrevSiblings()
    {
        return $this->prevSiblings()->count();
    }

    /**
     * @return bool
     */
    public function hasPrevSiblings()
    {
        return !!$this->countPrevSiblings();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function getNextSibling(array $columns = ['*'])
    {
        return $this->nextSibling($columns)->first();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getNextSiblings(array $columns = ['*'])
    {
        return $this->nextSiblings($columns)->get();
    }

    /**
     * @return int
     */
    public function countNextSiblings()
    {
        return $this->nextSiblings()->count();
    }

    /**
     * @return bool
     */
    public function hasNextSiblings()
    {
        return !!$this->countNextSiblings();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public static function getRoots(array $columns = ['*'])
    {
        return with(new static)->roots()->get();
    }

    /**
     * @param int $position
     * @return Entity
     */
    public function makeRoot($position)
    {
        return $this->moveTo($position, null);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public static function getTree(array $columns = ['*'])
    {
        return with(new static)->tree()->get()->toTree();
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
            $this->initClosureTable();

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

                $this->closure->insertNode((int)$ancestor, (int)$descendant);
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

    /**
     * @param EntityInterface $unsavedEntity
     */
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
     * @param bool $withAncestor
     * @param bool $forceDelete
     * @return mixed
     */
    public function deleteSubtree($withAncestor = false, $forceDelete = false)
    {
        $keyName = $this->getKeyName();
        $keys    = $this->getDescendants([$keyName]);

        if ($withAncestor === true)
        {
            $keys[]  = $this->getKey();
        }

        $query = $this->whereIn($keyName, $keys);

        return ($forceDelete === true ? $query->forceDelete() : $query->delete());
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

        $this->initClosureTable();

        $ctableAttrs = $this->closure->getRealAttributes();

        // Here we do a workaround to simplify QueryBuilder tests
        $attrs = [
            'pk' => $this->getQualifiedKeyName(),
            'pkValue' => $this->getKey(),
            'position' => EntityInterface::POSITION,
            'positionValue'   => $this->{EntityInterface::POSITION},
            'closure'         => $this->closure->getTable(),
            'ancestor'        => $this->closure->getQualifiedAncestorColumn(),
            'ancestorShort'   => ClosureTableInterface::ANCESTOR,
            'ancestorValue'   => $ctableAttrs[ClosureTableInterface::ANCESTOR],
            'descendant'      => $this->closure->getQualifiedDescendantColumn(),
            'descendantShort' => ClosureTableInterface::DESCENDANT,
            'depth'           => $this->closure->getQualifiedDepthColumn(),
            'depthShort'      => ClosureTableInterface::DEPTH,
            'depthValue'      => $ctableAttrs[ClosureTableInterface::DEPTH]
        ];

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor(), $attrs);
    }
}