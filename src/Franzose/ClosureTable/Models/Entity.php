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
     * ClosureTable model instance.
     *
     * @var ClosureTable
     */
    protected $closure = 'Franzose\ClosureTable\Models\ClosureTable';

    /**
     * Cached "previous" (i.e. before the model is moved) direct ancestor id of this model.
     *
     * @var int
     */
    protected $oldParentId;

    /**
     * Cached "previous" (i.e. before the model is moved) model position.
     *
     * @var int
     */
    protected $oldPosition;

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
     * Entity constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->fillable(array_merge($this->getFillable(), array(static::POSITION)));

        if ( ! isset($attributes[static::POSITION]))
        {
            $attributes[static::POSITION] = 0;
        }

        $this->closure = new $this->closure;

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

        static::saving(function($entity)
        {
            $entity->moveNode();
        });

        static::created(function($entity)
        {
            $entity->insertNode();
        });

        static::saved(function($entity)
        {
            $entity->reorderSiblings();
        });
    }

    /**
     * Applies base attributes to closure table object.
     *
     * @return void
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
     * Indicates whether the model is a parent.
     *
     * @return bool
     */
    public function isParent()
    {
        if ( ! $this->exists)
        {
            return false;
        }

        return $this->hasChildren();
    }

    /**
     * Indicates whether the model has no ancestors.
     *
     * @return bool
     */
    public function isRoot()
    {
        if ( ! $this->exists)
        {
            return false;
        }

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
        if ( ! $this->exists)
        {
            $result = null;
        }
        else
        {
            $result = $this->belongsTo(get_class($this), static::PARENT_ID)->first($columns);
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
        if ( ! $this->exists)
        {
            return new Collection;
        }

        return $this->ancestors($columns)->get();
    }

    /**
     * Returns a number of model's ancestors.
     *
     * @return int
     */
    public function countAncestors()
    {
        if ( ! $this->exists)
        {
            return 0;
        }

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
        if ( ! $this->exists)
        {
            return new Collection;
        }

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
        if ( ! $this->exists)
        {
            return new Collection;
        }

        return $this->getDescendants($columns)->toTree($this->getKey());
    }

    /**
     * Returns a number of model's descendants.
     *
     * @return int
     */
    public function countDescendants()
    {
        if ( ! $this->exists)
        {
            return 0;
        }

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
        if ( ! $this->exists)
        {
            $result = new Collection;
        }
        else if ($this->hasChildrenRelation())
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
        if ( ! $this->exists)
        {
            $result = 0;
        }
        else if ($this->hasChildrenRelation())
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
    public function hasChildrenRelation()
    {
        return array_key_exists(EntityInterface::CHILDREN, $this->getRelations());
    }

    /**
     * Pushes a new item to a relation.
     *
     * @param $relation
     * @param $value
     * @return $this
     */
    public function appendRelation($relation, $value)
    {
        if ( ! array_key_exists($relation, $this->getRelations()))
        {
            $this->setRelation($relation, new Collection([$value]));
        }
        else
        {
            $this->getRelation($relation)->add($value);
        }

        return $this;
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
        if ( ! $this->exists)
        {
            $result = null;
        }
        else if ($this->hasChildrenRelation())
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
        if ( ! $this->exists)
        {
            $result = null;
        }
        else if ($this->hasChildrenRelation())
        {
            $result = $this->getRelation(EntityInterface::CHILDREN)->last();
        }
        else
        {
            $result = $this->children($columns)->orderBy(static::POSITION, 'desc')->first();
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
        if ($this->exists)
        {
            $child->moveTo($position, $this);
        }

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

        if ($this->exists)
        {
            \DB::transaction(function() use($children)
            {
                $lastChild = $this->getLastChild([static::POSITION]);

                if (is_null($lastChild))
                {
                    $lastChildPosition = 0;
                }
                else
                {
                    $lastChildPosition = $lastChild->{static::POSITION};
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
        }

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
        if ($this->exists)
        {
            $this->removeChildAt($position, $forceDelete);
        }

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

        if ($this->exists)
        {
            $this->removeChildrenRange($from, $to, $forceDelete);
        }

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
        if ( ! $this->exists)
        {
            return new Collection;
        }

        return $this->siblings($columns)->get();
    }

    /**
     * Returns number of model's siblings.
     *
     * @return int
     */
    public function countSiblings()
    {
        if ( ! $this->exists)
        {
            return 0;
        }

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
        if ( ! $this->exists)
        {
            return new Collection;
        }

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
        if ( ! $this->exists)
        {
            return null;
        }

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
        if ( ! $this->exists)
        {
            return new Collection;
        }

        return $this->siblings($columns)->orderBy(static::POSITION, 'desc')->first();
    }

    /**
     * Retrieves immediate previous sibling of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getPrevSibling(array $columns = ['*'])
    {
        if ( ! $this->exists)
        {
            return null;
        }

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
        if ( ! $this->exists)
        {
            return new Collection;
        }

        return $this->prevSiblings($columns)->get();
    }

    /**
     * Returns number of previous siblings of a model.
     *
     * @return int
     */
    public function countPrevSiblings()
    {
        if ( ! $this->exists)
        {
            return 0;
        }

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
        if ( ! $this->exists)
        {
            return null;
        }

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
        if ( ! $this->exists)
        {
            return new Collection;
        }

        return $this->nextSiblings($columns)->get();
    }

    /**
     * Returns number of next siblings of a model.
     *
     * @return int
     */
    public function countNextSiblings()
    {
        if ( ! $this->exists)
        {
            return 0;
        }

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
        return with(new static)->tree(array_merge($columns, [static::PARENT_ID]))->get()->toTree();
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
        //$ancestorRealDepth = $ancestor->{static::REAL_DEPTH};
        $parentId = ($ancestor instanceof EntityInterface ? $ancestor->getKey() : $ancestor);

        if ($this->getKey() == $parentId)
        {
            throw new \InvalidArgumentException('Target entity is equal to the sender.');
        }

        $this->oldParentId = $this->{static::PARENT_ID};
        $this->{static::PARENT_ID} = $parentId;

        $this->oldPosition = $this->{static::POSITION};
        $this->{static::POSITION} = $position;

        //$this->{static::REAL_DEPTH} = $ancestorRealDepth+1;

        $this->save();

        return $this;
    }

    /**
     * Reorders model's siblings when one is moved to another position or ancestor.
     *
     * @param bool $parentIdChanged
     * @return void
     */
    protected function reorderSiblings($parentIdChanged = false)
    {
        if ( ! is_null($this->oldPosition))
        {
            $position = [
                'old' => $this->oldPosition,
                'now' => $this->{static::POSITION}
            ];

            list($range, $action) = $this->setupReordering($position, $parentIdChanged);

            $this->siblingsRange($range, QueryBuilder::BY_WHERE_IN)->$action(static::POSITION);
        }
    }

    /**
     * Setups model's siblings reordering.
     *
     * Actually, the method determines siblings that will be reordered
     * by creating range of theirs positions and determining the action
     * that will be used in reordering ('increment' or 'decrement').
     *
     * @param array $position
     * @param bool $parentIdChanged
     * @return array
     */
    protected function setupReordering($position, $parentIdChanged)
    {
        // If the model's parent was changed, firstly we decrement
        // positions of the 'old' next siblings of the model.
        if ($parentIdChanged === true)
        {
            $range  = $position['old'];
            $action = 'decrement';
        }
        else
        {
            if ($position['now'] > $position['old'])
            {
                $range  = range($position['old'], $position['now']);
                $action = 'decrement';
            }
            elseif ($position['now'] == $position['old'] && $position['now'] == 0)
            {
                $range = $position['now'];
                $action = 'increment';
            }
            else
            {
                if ($position['old'] != 0) $position['old']--;

                $range = range($position['now'], $position['old']);
                $action = 'increment';
            }
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
        $ancestor = (isset($this->{static::PARENT_ID}) ? $this->{static::PARENT_ID} : $descendant);

        $this->closure->insertNode($ancestor, $descendant);
    }

    /**
     * Moves node no another ancestor.
     *
     * @return void
     */
    protected function moveNode()
    {
        if ($this->exists && isset($this->{static::PARENT_ID}))
        {
            $this->initClosureTable();

            if ($this->{static::PARENT_ID} != $this->oldParentId)
            {
                $this->reorderSiblings(true);
            }

            $this->closure->moveNodeTo($this->{static::PARENT_ID});
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

    /**
     *
     *
     * @return string
     */
    public function getQualifiedPositionColumn()
    {
        return $this->getTable() . '.' . static::POSITION;
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
        $queryBuilder = null;

        $attrs = [
            'pk' => $this->getQualifiedKeyName(),
            'position' => $this->getQualifiedPositionColumn(),
            'closure'         => $this->closure->getTable(),
            'ancestor'        => $this->closure->getQualifiedAncestorColumn(),
            'ancestorShort'   => ClosureTableInterface::ANCESTOR,
            'descendant'      => $this->closure->getQualifiedDescendantColumn(),
            'descendantShort' => ClosureTableInterface::DESCENDANT,
            'depth'           => $this->closure->getQualifiedDepthColumn(),
            'depthShort'      => ClosureTableInterface::DEPTH,
        ];

        // We create the extended query builder only
        // if the model 'exists' to reduce database queries.
        if ( ! is_null($this->getKey()))
        {
            $this->initClosureTable();

            $ctableAttrs = $this->closure->getActualAttrs();

            // Workaround to simplify QueryBuilder queries construction.
            $attrs = array_merge($attrs, [
                'pkValue' => $this->getKey(),
                'positionValue'   => $this->{static::POSITION},
                'ancestorValue'   => $this->{static::PARENT_ID},
                'depthValue'      => $ctableAttrs[ClosureTableInterface::DEPTH]
            ]);
        }

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor(), $attrs);
    }
}