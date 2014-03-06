<?php namespace Franzose\ClosureTable\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Franzose\ClosureTable\Extensions\Collection;
use \Franzose\ClosureTable\Extensions\QueryBuilder;

/**
 * Basic entity class.
 *
 * Properties, listed below, are used to make the internal code cleaner.
 * However, if you named, for example, the position column to be "pos",
 * remember you can get its value either by $this->pos or $this->position.
 *
 * @property int position Alias for the current position property
 * @property int parent_id Alias for the direct ancestor identifier property
 * @property int real_depth Alias for the real depth property
 *
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
    protected $old_parent_id;

    /**
     * Cached "previous" (i.e. before the model is moved) model position.
     *
     * @var int
     */
    protected $old_position;

    /**
     * Cached "previous" (i.e. before the model is moved) model real depth.
     *
     * @var int
     */
    protected $old_real_depth;

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = true;

    /**
     * Indicates if the model is being moved to another ancestor.
     *
     * @var bool
     */
    protected $isMoved = false;

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
    public function __construct(array $attributes = [])
    {
        $position = $this->getPositionColumn();
        $depth = $this->getRealDepthColumn();

        $this->fillable(array_merge($this->getFillable(), [$position, $depth]));

        if ( ! isset($attributes[$position]))
        {
            $attributes[$position] = 0;
        }

        if ( ! isset($attributes[$depth]))
        {
            $attributes[$depth] = 0;
        }

        $this->closure = new $this->closure;

        parent::__construct($attributes);
    }

    /**
     * Gets value of the "parent id" attribute.
     *
     * @return int
     */
    public function getParentIdAttribute()
    {
        return $this->getAttributeFromArray($this->getParentIdColumn());
    }

    /**
     * Sets new parent id and caches the old one.
     *
     * @param int $value
     */
    public function setParentIdAttribute($value)
    {
        $column = $this->getParentIdColumn();

        $this->old_parent_id = $this->parent_id;
        $this->attributes[$column] = intval($value);
    }

    /**
     * Gets the fully qualified "parent id" column.
     *
     * @return string
     */
    public function getQualifiedParentIdColumn()
    {
        return $this->getTable() . '.' . static::PARENT_ID;
    }

    /**
     * Gets the short name of the "parent id" column.
     *
     * @return string
     */
    public function getParentIdColumn()
    {
        return static::PARENT_ID;
    }

    /**
     * Gets value of the "position" attribute.
     *
     * @return int
     */
    public function getPositionAttribute()
    {
        return $this->getAttributeFromArray($this->getPositionColumn());
    }

    /**
     * Sets new position and caches the old one.
     *
     * @param int $value
     */
    public function setPositionAttribute($value)
    {
        $this->old_position = $this->position;
        $this->attributes[$this->getPositionColumn()] = intval($value);
    }

    /**
     * Gets the fully qualified "position" column.
     *
     * @return string
     */
    public function getQualifiedPositionColumn()
    {
        return $this->getTable() . '.' . static::POSITION;
    }

    /**
     * Gets the short name of the "position" column.
     *
     * @return string
     */
    public function getPositionColumn()
    {
        return static::POSITION;
    }

    /**
     * Gets value of the "real depth" attribute.
     *
     * @return int
     */
    public function getRealDepthAttribute()
    {
        return $this->getAttributeFromArray($this->getRealDepthColumn());
    }

    /**
     * Sets value of the "real depth" attribute.
     *
     * @param int $value
     */
    public function setRealDepthAttribute($value)
    {
        $this->old_real_depth = $this->real_depth;
        $this->attributes[$this->getRealDepthColumn()] = intval($value);
    }

    /**
     * Gets the fully qualified "real depth" column.
     *
     * @return string
     */
    public function getQualifiedRealDepthColumn()
    {
        return $this->getTable() . '.' . static::REAL_DEPTH;
    }

    /**
     * Gets the short name of the "real depth" column.
     *
     * @return string
     */
    public function getRealDepthColumn()
    {
        return static::REAL_DEPTH;
    }

    /**
     * Gets the "children" relation index.
     *
     * @return string
     */
    public function getChildrenRelationIndex()
    {
        return static::CHILDREN;
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
            $result = $this->belongsTo(get_class($this), $this->getParentIdColumn())->first($columns);
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
            $result = $this->getRelation($this->getChildrenRelationIndex());
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
            $result = $this->getRelation($this->getChildrenRelationIndex())->count();
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
        return array_key_exists($this->getChildrenRelationIndex(), $this->getRelations());
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
            $result = $this->getRelation($this->getChildrenRelationIndex())->get($position);
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
            $result = $this->getRelation($this->getChildrenRelationIndex())->last();
        }
        else
        {
            $result = $this->children($columns)->orderBy($this->getPositionColumn(), 'desc')->first();
        }

        return $result;
    }

    /**
     * Gets last child position.
     *
     * @return int
     */
    protected function getLastChildPosition()
    {
        $lastChild = $this->getLastChild([$this->getPositionColumn()]);

        return (is_null($lastChild) ? 0 : $lastChild->position);
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
            if (is_null($position))
            {
                $position = $this->getNextAfterLastPosition($this->real_depth+1);
            }

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
                $lastChildPosition = $this->getLastChildPosition();

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
     * @return $this
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

        return $this->siblings($columns)->orderBy($this->getPositionColumn(), 'desc')->first();
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
     * @return \Franzose\ClosureTable\Extensions\Collection
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
     * @return \Franzose\ClosureTable\Extensions\Collection
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
     * @return \Franzose\ClosureTable\Extensions\Collection
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
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public static function getTree(array $columns = ['*'])
    {
        $instance = new static;

        return $instance->tree(array_merge($columns, [$instance->getParentIdColumn()]))->get()->toTree();
    }

    /**
     * Saves models from the given attributes array.
     *
     * @param array $tree
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public static function createFromArray(array $tree)
    {
        $childrenRelationIndex = with(new static)->getChildrenRelationIndex();
        $entities = [];

        foreach($tree as $item)
        {
            $children = array_pull($item, $childrenRelationIndex);

            /**
             * @var Entity $entity
             */
            $entity = new static($item);
            $entity->save();

            if ( ! is_null($children))
            {
                $children = static::createFromArray($children, true);
                $entity->setRelation($childrenRelationIndex, $children);
                $entity->appendChildren($children);
            }

            $entities[] = $entity;
        }

        return new Collection($entities);
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
        $parentId = ( ! $ancestor instanceof EntityInterface ? $ancestor : $ancestor->getKey());

        if ($this->parent_id == $parentId && ! is_null($this->parent_id))
        {
            return $this;
        }

        if ($this->getKey() == $parentId)
        {
            throw new \InvalidArgumentException('Target entity is equal to the sender.');
        }

        $this->parent_id  = $parentId;
        $this->position   = $position;
        $this->real_depth = $this->getNewRealDepth($ancestor);

        $this->isMoved = true;

        $this->save();

        $this->isMoved = false;

        return $this;
    }

    /**
     * Gets real depth of the new ancestor of the model.
     *
     * @param Entity|int|null $ancestor
     * @return int
     */
    protected function getNewRealDepth($ancestor)
    {
        if ( ! $ancestor instanceof EntityInterface)
        {
            if (is_null($ancestor))
            {
                $depth = 0;
            }
            else
            {
                $depth = static::find($ancestor)->real_depth;
            }
        }
        else
        {
            $depth = $ancestor->real_depth;
        }

        return ($depth === 0 ?: $depth + 1);
    }

    /**
     * Perform a model insert operation.
     *
     * @param  EloquentBuilder  $query
     * @return bool
     */
    protected function performInsert(EloquentBuilder $query)
    {
        if ($this->isMoved === false)
        {
            $this->position = $this->getNextAfterLastPosition();
        }

        return parent::performInsert($query);
    }

    /**
     * Gets the next sibling position after the last one at the given depth.
     *
     * @param int $depth
     * @return int
     */
    protected function getNextAfterLastPosition($depth = null)
    {
        $positionColumn = $this->getPositionColumn();
        $depthColumn = $this->getRealDepthColumn();

        $depth = ( ! is_null($depth) ?: $this->real_depth);

        $entity = $this->select($positionColumn)
            ->where($depthColumn, '=', $depth)
            ->orderBy($positionColumn, 'desc')
            ->first();

        if (is_null($entity))
        {
            $result = 0;
        }
        else
        {
            $result = $entity->position+1;
        }

        return $result;
    }

    /**
     * Reorders model's siblings when one is moved to another position or ancestor.
     *
     * @param bool $parentIdChanged
     * @return void
     */
    protected function reorderSiblings($parentIdChanged = false)
    {
        if ( ! is_null($this->old_position))
        {
            list($range, $action) = $this->setupReordering($parentIdChanged);

            $this->siblingsRange($range, QueryBuilder::BY_WHERE_IN)->$action($this->getPositionColumn());
        }
    }

    /**
     * Setups model's siblings reordering.
     *
     * Actually, the method determines siblings that will be reordered
     * by creating range of theirs positions and determining the action
     * that will be used in reordering ('increment' or 'decrement').
     *
     * @param bool $parentIdChanged
     * @return array
     */
    protected function setupReordering($parentIdChanged)
    {
        $position = [
            'old' => $this->old_position,
            'now' => $this->position
        ];

        $depth = [
            'old' => $this->old_real_depth,
            'now' => $this->real_depth
        ];

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
                // Prevent the first node to get -1 position
                if ($position['old'] == 0) $position['old']++;

                if ($depth['old'] == $depth['now'])
                {
                    $range  = range($position['old'], $position['now']);
                    $action = 'decrement';
                }
                else
                {
                    $range = $position['now'];
                    $action = 'increment';
                }
            }
            // Just increment positions of all next siblings
            elseif ($position['now'] == $position['old'] && $position['now'] == 0)
            {
                $range = $position['now'];
                $action = 'increment';
            }
            else
            {
                // Prevent the first node to get -1 position
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
        $ancestor = (isset($this->parent_id) ? $this->parent_id : $descendant);

        $this->closure->insertNode($ancestor, $descendant);
    }

    /**
     * Moves node no another ancestor.
     *
     * @return void
     */
    protected function moveNode()
    {
        if ($this->exists && isset($this->parent_id))
        {
            if (is_null($this->closure->{$this->closure->getAncestorColumn()}))
            {
                $primaryKey = $this->getKey();
                $this->closure->{$this->closure->getAncestorColumn()} = $primaryKey;
                $this->closure->{$this->closure->getDescendantColumn()} = $primaryKey;
                $this->closure->{$this->closure->getDepthColumn()} = 0;
            }

            if ($this->parent_id != $this->old_parent_id)
            {
                $this->reorderSiblings(true);
            }

            $this->closure->moveNodeTo($this->parent_id);
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

        // Workaround to simplify QueryBuilder queries construction.
        $attrs = [
            'pk' => $this->getQualifiedKeyName(),
            'position' => $this->getQualifiedPositionColumn(),
            'closure'         => $this->closure->getTable(),
            'ancestor'        => $this->closure->getQualifiedAncestorColumn(),
            'ancestorShort'   => $this->closure->getAncestorColumn(),
            'descendant'      => $this->closure->getQualifiedDescendantColumn(),
            'descendantShort' => $this->closure->getDescendantColumn(),
            'depth'           => $this->closure->getQualifiedDepthColumn(),
            'depthShort'      => $this->closure->getDepthColumn(),
        ];

        // We create the extended query builder only
        // if the model 'exists' to avoid null values.
        if ( ! is_null($this->getKey()))
        {
            $attrs = array_merge($attrs, [
                'pkValue' => $this->getKey(),
                'positionValue'   => $this->position,
                'ancestorValue'   => $this->parent_id,
                'depthValue'      => $this->real_depth
            ]);
        }

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor(), $attrs);
    }
}