<?php namespace Franzose\ClosureTable\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Franzose\ClosureTable\Contracts\ClosureTableInterface;
use \Franzose\ClosureTable\Extensions\Collection;
use \Franzose\ClosureTable\Extensions\QueryBuilder;

/**
 * Class Entity
 * @package Franzose\ClosureTable
 */
class Entity extends Eloquent implements EntityInterface {

    /**
     * Temporary copy of the 'before saved' model.
     *
     * @var Entity
     */
    protected $oldInstance;

    /**
     * Cached direct ancestor id of this model. Used to simplify the query.
     *
     * @var int
     */
    protected $parentId;

    protected $oldPosition;

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
        $this->fillable(array_merge($this->getFillable(), array(EntityInterface::POSITION)));

        if ( ! isset($attributes[EntityInterface::POSITION]))
        {
            $attributes[EntityInterface::POSITION] = 0;
        }

        $this->makeClosureTable();

        parent::__construct($attributes);
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function($entity){
            //static::newPositionSet(function($entity){
            //    $entity->reorderSiblings();
            //});
            //var_dump($entity->closure->getOldRealAttributes());
            //var_dump($entity->closure->getRealAttributes());
            $entity->moveNode();
            //var_dump($entity->closure->getOldRealAttributes());
        });

        static::created(function($entity){
            $entity->insertNode();
        });

        static::saved(function($entity){
            $entity->reorderSiblings();
        });
    }

    public static function newPositionSet($callback)
    {
        static::registerModelEvent('new-position-set', $callback);
    }

    public function setPositionAttribute($value)
    {
        $this->oldPosition = $this->getAttribute(EntityInterface::POSITION);
        $this->attributes[EntityInterface::POSITION] = $value;

        $this->fireModelEvent('new-position-set');
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
     * Indicates whether the model is a parent.
     *
     * @return bool
     */
    public function isParent()
    {
        return $this->hasChildren();
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
     * Retrieves direct ancestor of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getParent(array $columns = ['*'])
    {
        if ( ! is_null($this->parentId))
        {
            $result = static::find($this->parentId);
        }
        else
        {
            $result = $this->parent($columns)->first();
        }

        return $result;
    }

    /**
     * Retrieves all ancestors of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getAncestors(array $columns = ['*'])
    {
        return $this->ancestors($columns)->get();
    }

    /**
     * Returns a number of model's ancestors.
     *
     * @return int
     */
    public function countAncestors()
    {
        return (int)$this->ancestors()->count();
    }

    /**
     * Indicates whether a model has ancestors.
     *
     * @return bool
     */
    public function hasAncestors()
    {
        return !!$this->countAncestors();
    }

    /**
     * Retrieves all descendants of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getDescendants(array $columns = ['*'])
    {
        return $this->descendants($columns)->get();
    }

    /**
     * Retrieves all descendants of a model as a tree-like collection.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getDescendantsTree(array $columns = ['*'])
    {
        return $this->getDescendants($columns)->toTree($this->getKey());
    }

    /**
     * Returns a number of model's descendants.
     *
     * @return int
     */
    public function countDescendants()
    {
        return (int)$this->descendants()->count();
    }

    /**
     * Indicates whether a model has descendants.
     *
     * @return bool
     */
    public function hasDescendants()
    {
        return !!$this->countDescendants();
    }

    /**
     * Retrieves all children of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getChildren(array $columns = ['*'])
    {
        if ($this->hasChildrenRelation())
        {
            $result = $this->getRelation(EntityInterface::CHILDREN);
        }
        else
        {
            $result = $this->children($columns)->get();
        }

        return $result;
    }

    /**
     * Returns a number of model's children.
     *
     * @return int
     */
    public function countChildren()
    {
        if ($this->hasChildrenRelation())
        {
            $result = $this->getRelation(EntityInterface::CHILDREN)->count();
        }
        else
        {
            $result = $this->children()->count();
        }

        return (int)$result;
    }

    /**
     *  Indicates whether a model has children.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return !!$this->countChildren();
    }

    /**
     * Indicates whether a model has children as a relation.
     *
     * @return bool
     */
    protected function hasChildrenRelation()
    {
        return array_key_exists(EntityInterface::CHILDREN, $this->getRelations());
    }

    /**
     * Retrieves a child with given position.
     *
     * @param $position
     * @param array $columns
     * @return Entity
     */
    public function getChildAt($position, array $columns = ['*'])
    {
        if ($this->hasChildrenRelation())
        {
            $result = $this->getRelation(EntityInterface::CHILDREN)->get($position);
        }
        else
        {
            $result = $this->childAt($position, $columns)->first();
        }

        return $result;
    }

    /**
     * Retrieves the first child.
     *
     * @param array $columns
     * @return Entity
     */
    public function getFirstChild(array $columns = ['*'])
    {
        return $this->getChildAt(0, $columns);
    }

    /**
     * Retrieves the last child.
     *
     * @param array $columns
     * @return Entity
     */
    public function getLastChild(array $columns = ['*'])
    {
        if ($this->hasChildrenRelation())
        {
            $result = $this->getRelation(EntityInterface::CHILDREN)->last();
        }
        else
        {
            $result = $this->children($columns)->orderBy(EntityInterface::POSITION, 'desc')->first();
        }

        return $result;
    }

    /**
     * Appends a child to the model.
     *
     * @param EntityInterface $child
     * @param int $position
     * @return $this
     */
    public function appendChild(EntityInterface $child, $position = null)
    {
        $child->moveTo($position, $this);

        return $this;
    }

    /**
     * Appends a collection of children to the model.
     *
     * @param Collection|\Illuminate\Database\Eloquent\Collection $children
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function appendChildren($children)
    {
        $validInstance = (   $children instanceof \Illuminate\Database\Eloquent\Collection
                          || $children instanceof Collection);

        if ( ! $validInstance)
        {
            throw new \InvalidArgumentException('Children argument must be a collection type');
        }

        \DB::transaction(function() use($children)
        {
            $lastChild = $this->getLastChild([EntityInterface::POSITION]);

            if (is_null($lastChild))
            {
                $lastChildPosition = 0;
            }
            else
            {
                $lastChildPosition = $lastChild->{EntityInterface::POSITION};
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
     * Removes a model's child with given position.
     *
     * @param int $position
     * @param bool $forceDelete
     * @return $this
     */
    public function removeChild($position = null, $forceDelete = false)
    {
        $this->removeChildAt($position, $forceDelete);

        return $this;
    }

    /**
     * Removes model's children within a range of positions.
     *
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     * @return Entity
     * @throws \InvalidArgumentException
     */
    public function removeChildren($from, $to = null, $forceDelete = false)
    {
        if ( ! is_numeric($from) || ( ! is_null($to) && ! is_numeric($to)))
        {
            throw new \InvalidArgumentException('`from` and `to` are the position boundaries. They must be of type int.');
        }

        $this->removeChildrenRange($from, $to, $forceDelete);

        return $this;
    }

    /**
     * Retrives all siblings of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getSiblings(array $columns = ['*'])
    {
        return $this->siblings($columns)->get();
    }

    /**
     * Returns number of model's siblings.
     *
     * @return int
     */
    public function countSiblings()
    {
        return $this->siblings()->count();
    }

    /**
     * Indicates whether a model has siblings.
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return !!$this->countSiblings();
    }

    /**
     * Retrieves neighbors (immediate previous and immmediate next models) of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getNeighbors(array $columns = ['*'])
    {
        return $this->neighbors($columns)->get();
    }

    /**
     * Retrieves a model's sibling with given position.
     *
     * @param int $position
     * @param array $columns
     * @return Entity
     */
    public function getSiblingAt($position, array $columns = ['*'])
    {
        return $this->siblingAt($position, $columns)->first();
    }

    /**
     * Retrieves the first model's sibling.
     *
     * @param array $columns
     * @return Entity
     */
    public function getFirstSibling(array $columns = ['*'])
    {
        return $this->getSiblingAt(0, $columns);
    }

    /**
     * Retrieves the last model's sibling.
     *
     * @param array $columns
     * @return Entity
     */
    public function getLastSibling(array $columns = ['*'])
    {
        return $this->siblings($columns)->orderBy(EntityInterface::POSITION, 'desc')->first();
    }

    /**
     * Retrieves immediate previous sibling of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getPrevSibling(array $columns = ['*'])
    {
        return $this->prevSibling($columns)->first();
    }

    /**
     * Retrieves all previous siblings of a model.
     *
     * @param array $columns
     * @return mixed
     */
    public function getPrevSiblings(array $columns = ['*'])
    {
        return $this->prevSiblings($columns)->get();
    }

    /**
     * Returns number of previous siblings of a model.
     *
     * @return int
     */
    public function countPrevSiblings()
    {
        return $this->prevSiblings()->count();
    }

    /**
     * Indicates whether a model has previous siblings.
     *
     * @return bool
     */
    public function hasPrevSiblings()
    {
        return !!$this->countPrevSiblings();
    }

    /**
     * Retrieves immediate next sibling of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getNextSibling(array $columns = ['*'])
    {
        return $this->nextSibling($columns)->first();
    }

    /**
     * Retrieves all next siblings of a model.
     *
     * @param array $columns
     * @return mixed
     */
    public function getNextSiblings(array $columns = ['*'])
    {
        return $this->nextSiblings($columns)->get();
    }

    /**
     * Returns number of next siblings of a model.
     *
     * @return int
     */
    public function countNextSiblings()
    {
        return $this->nextSiblings()->count();
    }

    /**
     * Indicates whether a model has next siblings.
     *
     * @return bool
     */
    public function hasNextSiblings()
    {
        return !!$this->countNextSiblings();
    }

    /**
     * Retrieves root (with no ancestors) models.
     *
     * @param array $columns
     * @return mixed
     */
    public static function getRoots(array $columns = ['*'])
    {
        return with(new static)->roots()->get();
    }

    /**
     * Makes model a root with given position.
     *
     * @param int $position
     * @return Entity
     */
    public function makeRoot($position)
    {
        return $this->moveTo($position, null);
    }

    /**
     * Retrieves entire tree.
     *
     * @param array $columns
     * @return mixed
     */
    public static function getTree(array $columns = ['*'])
    {
        return with(new static)->tree()->get($columns)->toTree();
    }

    /**
     * Makes the model a child or a root with given position.
     *
     * @param int $position
     * @param EntityInterface|int $ancestor
     * @return Entity
     * @throws \InvalidArgumentException
     */
    public function moveTo($position, $ancestor = null)
    {
        $ancestor = ($ancestor instanceof EntityInterface ? $ancestor->getKey() : $ancestor);

        if ($this->getKey() == $ancestor)
        {
            throw new \InvalidArgumentException('Target entity is equal to the sender.');
        }

        $this->parentId = $ancestor;
        $this->{EntityInterface::POSITION} = $position;

        $this->save();

        return $this;
    }

    /*protected function reorderSiblingsOnSaving()
    {
        if ( ! is_null($this->oldPosition))
        {
            //
        }
    }*/

    /**
     * Reorders siblings when a model is moved to another position or ancestor.
     *
     * @return void
     */
    protected function reorderSiblings()
    {
        if ( ! is_null($this->oldPosition))
        {
            $position = [
                'old' => $this->oldPosition,
                'now' => $this->{EntityInterface::POSITION}
            ];

            //$depth = [
            //    'old' => $this->closure->getOldRealAttributes(ClosureTableInterface::DEPTH),
            //    'now' => $this->closure->getRealAttributes(ClosureTableInterface::DEPTH)
            //];

            list($range, $action) = $this->getReorderingRangeAndAction($position['old'], $position['now']);

            $this->siblingsRange($range, QueryBuilder::BY_WHERE_IN)->$action(EntityInterface::POSITION);

            //if ($depth['now'] != $depth['old'])
            //{
                //$this->
                //var_dump($this->oldInstance->closure->getOldRealAttributes([ClosureTableInterface::DEPTH]));
                //$this->oldInstance->reorderSiblingsWithin($this->oldPosition, 'decrement');
            //}

            //var_dump(\DB::getQueryLog());
        }
    }

    protected function getReorderingRangeAndAction($oldPosition, $curPosition)
    {
        if ($curPosition > $oldPosition)
        {
            $range  = range($oldPosition, $curPosition);
            $action = 'decrement';
        }
        elseif ($curPosition == $oldPosition && $curPosition == 0)
        {
            $range = $curPosition;
            $action = 'increment';
        }
        else
        {
            if ($oldPosition != 0) $oldPosition--;

            $range = range($curPosition, $oldPosition);
            $action = 'increment';
        }

        return [$range, $action];
    }

    /**
     * Inserts new node to closure table.
     *
     * @return void
     */
    protected function insertNode()
    {
        $descendant = $this->getKey();
        $ancestor = (isset($this->parentId) ? $this->parentId : $descendant);

        $this->closure->insertNode($ancestor, $descendant);
    }

    /**
     * Moves node no another ancestor.
     *
     * @return void
     */
    protected function moveNode()
    {
        if ($this->exists)
        {
            $this->initClosureTable();

            if (isset($this->parentId))
            {
                $this->closure->moveNodeTo($this->parentId);
            }
        }
    }

    /**
     * Deletes a subtree from database.
     *
     * @param bool $withSelf
     * @param bool $forceDelete
     * @return mixed
     */
    public function deleteSubtree($withSelf = false, $forceDelete = false)
    {
        $action = ($forceDelete === true ? 'forceDelete' : 'delete');
        $what = ($withSelf === true ? QueryBuilder::ALL_INC_SELF : QueryBuilder::ALL_BUT_SELF);
        $type = QueryBuilder::BY_WHERE_IN;

        return $this->descendants([$this->getQualifiedKeyName()], $what, $type)->$action();
    }

    public function getQualifiedPositionColumn()
    {
        return $this->getTable() . '.' . EntityInterface::POSITION;
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
            'position' => $this->getQualifiedPositionColumn(),
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