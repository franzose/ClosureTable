<?php
namespace Franzose\ClosureTable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Franzose\ClosureTable\Extensions\QueryBuilder;
use Franzose\ClosureTable\Contracts\EntityInterface;
use Franzose\ClosureTable\Extensions\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Throwable;

/**
 * Basic entity class.
 *
 * Properties, listed below, are used to make the internal code cleaner.
 * However, if you named, for example, the position column to be "pos",
 * remember you can get its value either by $this->pos or $this->position.
 *
 * @property int position Alias for the current position attribute name
 * @property int parent_id Alias for the direct ancestor identifier attribute name
 * @property int real_depth Alias for the real depth attribute name
 * @property Collection children Child nodes loaded from the database
 * @method HasMany childAt(int $position)
 * @method HasMany firstChild()
 * @method HasMany lastChild()
 * @method HasMany childrenRange(int $from, int $to = null)
 * @method Builder sibling()
 * @method Builder siblings()
 * @method Builder neighbors()
 * @method Builder siblingAt(int $position)
 * @method Builder firstSibling()
 * @method Builder lastSibling()
 * @method Builder prevSibling()
 * @method Builder prevSiblings()
 * @method Builder nextSibling()
 * @method Builder nextSiblings()
 * @method Builder siblingsRange(int $from, int $to = null)
 *
 * @package Franzose\ClosureTable
 */
class Entity extends Eloquent implements EntityInterface
{
    /**
     * ClosureTable model instance.
     *
     * @var ClosureTable
     */
    protected $closure = ClosureTable::class;

    /**
     * Cached "previous" (i.e. before the model is moved) direct ancestor id of this model.
     *
     * @var int
     */
    private $previousParentId;

    /**
     * Cached "previous" (i.e. before the model is moved) model position.
     *
     * @var int
     */
    private $previousPosition;

    /**
     * Cached "previous" (i.e. before the model is moved) model real depth.
     *
     * @var int
     */
    private $previousRealDepth;

    /**
     * Indicates if the model is being moved to another ancestor.
     *
     * @var bool
     */
    private $isMoved = false;

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

    public static $debug = false;

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

        if (isset($attributes[$position]) && $attributes[$position] < 0) {
            $attributes[$position] = 0;
        }

        if (!isset($attributes[$depth]) || $attributes[$depth] < 0) {
            $attributes[$depth] = 0;
        }

        $this->closure = new $this->closure;

        // The default class name of the closure table was not changed
        // so we define and set default closure table name automagically.
        // This can prevent useless copy paste of closure table models.
        if (get_class($this->closure) === ClosureTable::class) {
            $table = $this->getTable() . '_closure';
            $this->closure->setTable($table);
        }

        parent::__construct($attributes);
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = parent::newFromBuilder($attributes);
        $instance->previousParentId = $instance->parent_id;
        $instance->previousPosition = $instance->position;
        $instance->previousRealDepth = $instance->real_depth;
        return $instance;
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
        if ($this->parent_id === $value) {
            return;
        }
        $this->previousParentId = $this->parent_id;
        $this->attributes[$this->getParentIdColumn()] = $value;
    }

    /**
     * Gets the fully qualified "parent id" column.
     *
     * @return string
     */
    public function getQualifiedParentIdColumn()
    {
        return $this->getTable() . '.' . $this->getParentIdColumn();
    }

    /**
     * Gets the short name of the "parent id" column.
     *
     * @return string
     */
    public function getParentIdColumn()
    {
        return 'parent_id';
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
        if ($this->position === $value) {
            return;
        }
        $this->previousPosition = $this->position;
        $this->attributes[$this->getPositionColumn()] = (int) $value;
    }

    /**
     * Gets the fully qualified "position" column.
     *
     * @return string
     */
    public function getQualifiedPositionColumn()
    {
        return $this->getTable() . '.' . $this->getPositionColumn();
    }

    /**
     * Gets the short name of the "position" column.
     *
     * @return string
     */
    public function getPositionColumn()
    {
        return 'position';
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
    protected function setRealDepthAttribute($value)
    {
        if ($this->real_depth === $value) {
            return;
        }
        $this->previousRealDepth = $this->real_depth;
        $this->attributes[$this->getRealDepthColumn()] = (int) $value;
    }

    /**
     * Gets the fully qualified "real depth" column.
     *
     * @return string
     */
    public function getQualifiedRealDepthColumn()
    {
        return $this->getTable() . '.' . $this->getRealDepthColumn();
    }

    /**
     * Gets the short name of the "real depth" column.
     *
     * @return string
     */
    public function getRealDepthColumn()
    {
        return 'real_depth';
    }

    /**
     * Gets the "children" relation index.
     *
     * @return string
     */
    public function getChildrenRelationIndex()
    {
        return 'children';
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // If model's parent identifier was changed,
        // the closure table rows will update automatically.
        static::saving(function (Entity $entity) {
            $entity->clampPosition();
            $entity->moveNode();
        });

        // When entity is created, the appropriate
        // data will be put into the closure table.
        static::created(function (Entity $entity) {
            $entity->previousParentId = false;
            $entity->previousPosition = $entity->position;
            $entity->insertNode();
        });

        // Everytime the model's position or parent
        // is changed, its siblings reordering will happen,
        // so they will always keep the proper order.
        static::saved(function (Entity $entity) {
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
        return $this->exists && $this->hasChildren();
    }

    /**
     * Indicates whether the model has no ancestors.
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->exists && $this->parent_id === null;
    }

    /**
     * Retrieves direct ancestor of a model.
     *
     * @param array $columns
     * @return Entity|null
     */
    public function getParent(array $columns = ['*'])
    {
        return $this->exists ? $this->find($this->parent_id, $columns) : null;
    }

    /**
     * Builds closure table join based on the given column.
     *
     * @param string $column
     * @param bool $withSelf
     * @return QueryBuilder
     */
    protected function joinClosureBy($column, $withSelf = false)
    {
        $primary = $this->getQualifiedKeyName();
        $closure = $this->closure->getTable();
        $ancestor = $this->closure->getQualifiedAncestorColumn();
        $descendant = $this->closure->getQualifiedDescendantColumn();

        switch ($column) {
            case 'ancestor':
                $query = $this->join($closure, $ancestor, '=', $primary)
                    ->where($descendant, '=', $this->getKey());
                break;

            case 'descendant':
                $query = $this->join($closure, $descendant, '=', $primary)
                    ->where($ancestor, '=', $this->getKey());
                break;
        }

        $depthOperator = ($withSelf === true ? '>=' : '>');

        $query->where($this->closure->getQualifiedDepthColumn(), $depthOperator, 0);

        return $query;
    }

    /**
     * Builds closure table "where in" query on the given column.
     *
     * @param string $column
     * @param bool $withSelf
     * @return QueryBuilder
     */
    protected function subqueryClosureBy($column, $withSelf = false)
    {
        $self = $this;

        return $this->whereIn($this->getQualifiedKeyName(), function ($qb) use ($self, $column, $withSelf) {
            switch ($column) {
                case 'ancestor':
                    $selectedColumn = $self->closure->getAncestorColumn();
                    $whereColumn = $self->closure->getDescendantColumn();
                    break;

                case 'descendant':
                    $selectedColumn = $self->closure->getDescendantColumn();
                    $whereColumn = $self->closure->getAncestorColumn();
                    break;
            }

            $depthOperator = ($withSelf === true ? '>=' : '>');

            return $qb->select($selectedColumn)
                ->from($self->closure->getTable())
                ->where($whereColumn, '=', $self->getKey())
                ->where($self->closure->getDepthColumn(), $depthOperator, 0);
        });
    }

    /**
     * Retrieves all ancestors of a model.
     *
     * @param array $columns
     * @return Collection
     */
    public function getAncestors(array $columns = ['*'])
    {
        return $this->joinClosureBy('ancestor')->get($columns);
    }

    /**
     * Retrieves tree structured ancestors of a model.
     *
     * @param array $columns
     * @return Collection
     */
    public function getAncestorsTree(array $columns = ['*'])
    {
        return $this->getAncestors($columns)->toTree();
    }

    /**
     * Retrieves ancestors applying given conditions.
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @param array $columns
     * @return Collection
     */
    public function getAncestorsWhere($column, $operator = null, $value = null, array $columns = ['*'])
    {
        return $this->joinClosureBy('ancestor')->where($column, $operator, $value)->get($columns);
    }

    /**
     * Returns a number of model's ancestors.
     *
     * @return int
     */
    public function countAncestors()
    {
        return $this->joinClosureBy('ancestor')->count();
    }

    /**
     * Indicates whether a model has ancestors.
     *
     * @return bool
     */
    public function hasAncestors()
    {
        return (bool) $this->countAncestors();
    }

    /**
     * Retrieves all descendants of a model.
     *
     * @param array $columns
     * @return Collection
     */
    public function getDescendants(array $columns = ['*'])
    {
        return $this->joinClosureBy('descendant')->get($columns);
    }

    /**
     * Retrieves tree structured descendants of a model.
     *
     * @param array $columns
     * @return Collection
     */
    public function getDescendantsTree(array $columns = ['*'])
    {
        return $this->getDescendants($columns)->toTree();
    }

    /**
     * Retrieves descendants applying given conditions.
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @param array $columns
     * @return Collection
     */
    public function getDescendantsWhere($column, $operator = null, $value = null, array $columns = ['*'])
    {
        return $this->joinClosureBy('descendant')->where($column, $operator, $value)->get($columns);
    }

    /**
     * Returns a number of model's descendants.
     *
     * @return int
     */
    public function countDescendants()
    {
        return $this->joinClosureBy('descendant')->count();
    }

    /**
     * Indicates whether a model has descendants.
     *
     * @return bool
     */
    public function hasDescendants()
    {
        return (bool) $this->countDescendants();
    }

    /**
     * Returns one-to-many relationship to child nodes.
     *
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(get_class($this), $this->getParentIdColumn());
    }

    /**
     * Retrieves all children of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getChildren(array $columns = ['*'])
    {
        return $this->children()->get($columns);
    }

    /**
     * Returns a number of model's children.
     *
     * @return int
     */
    public function countChildren()
    {
        return $this->children()->count();
    }

    /**
     *  Indicates whether a model has children.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return (bool) $this->countChildren();
    }

    /**
     * Indicates whether a model has children as a relation.
     *
     * @return bool
     * @deprecated from 6.0
     */
    public function hasChildrenRelation()
    {
        return $this->relationLoaded($this->getChildrenRelationIndex());
    }

    /**
     * Returns relationship to a child at the given position.
     *
     * @param Builder $builder
     * @param int $position
     *
     * @return HasMany
     */
    public function scopeChildAt($builder, $position)
    {
        return $this->children()->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Retrieves a child with given position.
     *
     * @param int $position
     * @param array $columns
     * @return Entity
     */
    public function getChildAt($position, array $columns = ['*'])
    {
        return $this->childAt($position)->first($columns);
    }

    /**
     * Returns relationship to the first child node.
     *
     * @return HasMany
     */
    public function scopeFirstChild()
    {
        return $this->childAt(0);
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
     * Returns relationship to the last child node.
     *
     * @return HasMany
     */
    public function scopeLastChild()
    {
        return $this->children()->orderByDesc($this->getPositionColumn());
    }

    /**
     * Retrieves the last child.
     *
     * @param array $columns
     * @return Entity
     */
    public function getLastChild(array $columns = ['*'])
    {
        return $this->lastChild()->first($columns);
    }

    /**
     * Returns relationship to child nodes in the range of the given positions.
     *
     * @param Builder $builder
     * @param int $from
     * @param int|null $to
     *
     * @return HasMany
     */
    public function scopeChildrenRange($builder, $from, $to = null)
    {
        $position = $this->getPositionColumn();
        $query = $this->children()->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Retrieves children within given positions range.
     *
     * @param int $from
     * @param int $to
     * @param array $columns
     * @return Collection
     */
    public function getChildrenRange($from, $to = null, array $columns = ['*'])
    {
        return $this->childrenRange($from, $to)->get($columns);
    }

    /**
     * Appends a child to the model.
     *
     * @param EntityInterface $child
     * @param int $position
     * @param bool $returnChild
     * @return EntityInterface
     */
    public function addChild(EntityInterface $child, $position = null, $returnChild = false)
    {
        if ($this->exists) {
            $position = $position ?: $this->getLatestPosition();

            $child->moveTo($position, $this);
        }

        return $returnChild === true ? $child : $this;
    }

    /**
     * Appends a collection of children to the model.
     *
     * @param Entity[] $children
     * @param int $from
     *
     * @return Entity
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function addChildren(array $children, $from = null)
    {
        if (!$this->exists) {
            return $this;
        }

        $this->getConnection()->transaction(function () use (&$from, $children) {
            foreach ($children as $child) {
                $this->addChild($child, $from);
                $from++;
            }
        });

        return $this;
    }

    /**
     * Gets last child position.
     *
     * @return int
     */
    protected function getLastChildPosition()
    {
        $lastChild = $this->getLastChild([$this->getPositionColumn()]);

        return $lastChild === null ? 0 : $lastChild->position;
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
        if (!$this->exists) {
            return $this;
        }

        $action = ($forceDelete === true ? 'forceDelete' : 'delete');

        $this->childAt($position)->{$action}();

        return $this;
    }

    /**
     * Removes model's children within a range of positions.
     *
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     * @return $this
     * @throws InvalidArgumentException
     */
    public function removeChildren($from, $to = null, $forceDelete = false)
    {
        if (!is_numeric($from) || ($to !== null && !is_numeric($to))) {
            throw new InvalidArgumentException('`from` and `to` are the position boundaries. They must be of type int.');
        }

        if (!$this->exists) {
            return $this;
        }

        $action = ($forceDelete === true ? 'forceDelete' : 'delete');

        $this->childrenRange($from, $to)->{$action}();

        return $this;
    }

    /**
     * Builds a part of the siblings query.
     *
     * @param string|int|array $direction
     * @param int|bool $parentId
     * @param string $order
     *
     * @return QueryBuilder
     */
    protected function siblingsQuery($direction = '', $parentId = false, $order = 'asc')
    {
        $parentId = ($parentId === false ? $this->parent_id : $parentId);

        /**
         * @var QueryBuilder $query
         */
        $query = $this->where($this->getParentIdColumn(), '=', $parentId);

        $column = $this->getPositionColumn();

        switch ($direction) {
            case static::QUERY_ALL:
                $query->where($column, '<>', $this->position)->orderBy($column, $order);
                break;

            case static::QUERY_PREV_ALL:
                $query->where($column, '<', $this->position)->orderBy($column, $order);
                break;

            case static::QUERY_PREV_ONE:
                $query->where($column, '=', $this->position - 1);
                break;

            case static::QUERY_NEXT_ALL:
                $query->where($column, '>', $this->position)->orderBy($column, $order);
                break;

            case static::QUERY_NEXT_ONE:
                $query->where($column, '=', $this->position + 1);
                break;

            case static::QUERY_NEIGHBORS:
                $query->whereIn($column, [$this->position - 1, $this->position + 1]);
                break;

            case static::QUERY_LAST:
                $query->orderBy($column, 'desc');
                break;
        }

        if (is_int($direction)) {
            $query->where($column, '=', $direction);
        } else if (is_array($direction)) {
            $query->buildWherePosition($this->getPositionColumn(), $direction);
        }

        return $query;
    }

    /**
     * Returns sibling query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeSibling(Builder $builder)
    {
        return $builder->where($this->getParentIdColumn(), '=', $this->parent_id);
    }

    /**
     * Returns siblings query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeSiblings(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '<>', $this->position);
    }

    /**
     * Retrives all siblings of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getSiblings(array $columns = ['*'])
    {
        return $this->siblings()->get($columns);
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
        return (bool) $this->countSiblings();
    }

    /**
     * Returns neighbors query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeNeighbors(Builder $builder)
    {
        $position = $this->position;

        return $this
            ->scopeSibling($builder)
            ->whereIn($this->getPositionColumn(), [$position - 1, $position + 1]);
    }

    /**
     * Retrieves neighbors (immediate previous and immediate next models) of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getNeighbors(array $columns = ['*'])
    {
        return $this->neighbors()->get($columns);
    }

    /**
     * Returns query builder for a sibling at the given position.
     *
     * @param Builder $builder
     * @param int $position
     *
     * @return Builder
     */
    public function scopeSiblingAt(Builder $builder, $position)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '=', $position);
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
        return $this->siblingAt($position)->first($columns);
    }

    /**
     * Returns query builder for the first sibling.
     *
     * @return Builder
     */
    public function scopeFirstSibling()
    {
        return $this->siblingAt(0);
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
     * Returns query builder for the last sibling.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeLastSibling(Builder $builder)
    {
        return $this->scopeSibling($builder)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Retrieves the last model's sibling.
     *
     * @param array $columns
     * @return Entity
     */
    public function getLastSibling(array $columns = ['*'])
    {
        return $this->lastSibling()->first($columns);
    }

    /**
     * Returns query builder for the previous sibling.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopePrevSibling(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '=', $this->position - 1);
    }

    /**
     * Retrieves immediate previous sibling of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getPrevSibling(array $columns = ['*'])
    {
        return $this->prevSibling()->first($columns);
    }

    /**
     * Returns query builder for the previous siblings.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopePrevSiblings(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '<', $this->position);
    }

    /**
     * Retrieves all previous siblings of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getPrevSiblings(array $columns = ['*'])
    {
        return $this->prevSiblings()->get($columns);
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
        return (bool) $this->countPrevSiblings();
    }

    /**
     * Returns query builder for the next sibling.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeNextSibling(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '=', $this->position + 1);
    }

    /**
     * Retrieves immediate next sibling of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getNextSibling(array $columns = ['*'])
    {
        return $this->nextSibling()->first($columns);
    }

    /**
     * Returns query builder for the next siblings.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeNextSiblings(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '>', $this->position);
    }

    /**
     * Retrieves all next siblings of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getNextSiblings(array $columns = ['*'])
    {
        return $this->nextSiblings()->get($columns);
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
        return (bool) $this->countNextSiblings();
    }

    /**
     * Returns query builder for a range of siblings.
     *
     * @param Builder $builder
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeSiblingsRange(Builder $builder, $from, $to = null)
    {
        $position = $this->getPositionColumn();

        $query = $this
            ->scopeSiblings($builder)
            ->where($position, '>=', $from);

        if ($to === null) {
            return $query;
        }

        return $query->where($position, '<=', $to);
    }

    /**
     * Retrieves siblings within given positions range.
     *
     * @param int $from
     * @param int $to
     * @param array $columns
     * @return Collection
     */
    public function getSiblingsRange($from, $to = null, array $columns = ['*'])
    {
        return $this->siblingsRange($from, $to)->get($columns);
    }

    /**
     * Appends a sibling within the current depth.
     *
     * @param EntityInterface $sibling
     * @param int|null $position
     * @param bool $returnSibling
     * @return EntityInterface
     */
    public function addSibling(EntityInterface $sibling, $position = null, $returnSibling = false)
    {
        if ($this->exists) {
            if ($position === null) {
                $position = $this->getLatestPosition();
            }

            $sibling->moveTo($position, $this->parent_id);
        }

        return ($returnSibling === true ? $sibling : $this);
    }

    /**
     * Appends multiple siblings within the current depth.
     *
     * @param array $siblings
     * @param int|null $from
     * @return $this
     */
    public function addSiblings(array $siblings, $from = null)
    {
        if ($this->exists) {
            if ($from === null) {
                $from = $this->getLatestPosition();
            }

            $parent = $this->getParent();
            /**
             * @var Entity $sibling
             */
            foreach ($siblings as $sibling) {
                $sibling->moveTo($from, $parent);
                $from++;
            }
        }

        return $this;
    }

    /**
     * Retrieves root (with no ancestors) models.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public static function getRoots(array $columns = ['*'])
    {
        /**
         * @var Entity $instance
         */
        $instance = new static;

        return $instance->whereNull($instance->getParentIdColumn())->get($columns);
    }

    /**
     * Makes model a root with given position.
     *
     * @param int $position
     * @return $this
     */
    public function makeRoot($position)
    {
        return $this->moveTo($position, null);
    }

    /**
     * Adds "parent id" column to columns list for proper tree querying.
     *
     * @param array $columns
     * @return array
     */
    protected function prepareTreeQueryColumns(array $columns)
    {
        return ($columns === ['*'] ? $columns : array_merge($columns, [$this->getParentIdColumn()]));
    }

    /**
     * Retrieves entire tree.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public static function getTree(array $columns = ['*'])
    {
        /**
         * @var Entity $instance
         */
        $instance = new static;

        return $instance->orderBy($instance->getParentIdColumn())->orderBy($instance->getPositionColumn())
            ->get($instance->prepareTreeQueryColumns($columns))->toTree();
    }

    /**
     * Retrieves tree by condition.
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @param array $columns
     *
     * @return Collection
     */
    public static function getTreeWhere($column, $operator = null, $value = null, array $columns = ['*'])
    {
        /**
         * @var Entity $instance
         */
        $instance = new static;
        $columns = $instance->prepareTreeQueryColumns($columns);

        return $instance->where($column, $operator, $value)->get($columns)->toTree();
    }

    /**
     * Retrieves tree with any conditions using QueryBuilder
     *
     * @param EloquentBuilder $query
     * @param array $columns
     *
     * @return Collection
     */
    public static function getTreeByQuery(Builder $query, array $columns = ['*'])
    {
        /**
         * @var Entity $instance
         */
        $instance = new static;
        $columns = $instance->prepareTreeQueryColumns($columns);
        return $query->get($columns)->toTree();
    }

    /**
     * Saves models from the given attributes array.
     *
     * @param array $tree
     * @param \Franzose\ClosureTable\Contracts\EntityInterface $parent
     *
     * @return Collection
     */
    public static function createFromArray(array $tree, EntityInterface $parent = null)
    {
        $childrenRelationIndex = with(new static)->getChildrenRelationIndex();
        $entities = [];

        foreach ($tree as $item) {
            $children = array_pull($item, $childrenRelationIndex);

            /**
             * @var Entity $entity
             */
            $entity = new static($item);
            $entity->parent_id = $parent ? $parent->getKey() : null;
            $entity->save();

            if ($children !== null) {
                $children = static::createFromArray($children, $entity);
                $entity->setRelation($childrenRelationIndex, $children);
                $entity->addChildren($children->all());
            }

            $entities[] = $entity;
        }

        return new Collection($entities);
    }

    /**
     * Makes the model a child or a root with given position. Do not use moveTo to move a node within the same ancestor (call position = value and save instead).
     *
     * @param int $position
     * @param EntityInterface|int $ancestor
     * @return Entity
     * @throws InvalidArgumentException
     */
    public function moveTo($position, $ancestor = null)
    {
        $parentId = (!$ancestor instanceof EntityInterface ? $ancestor : $ancestor->getKey());

        if ($this->parent_id === $parentId && $this->parent_id !== null) {
            return $this;
        }

        if ($this->getKey() === $parentId) {
            throw new InvalidArgumentException('Target entity is equal to the sender.');
        }

        $this->parent_id = $parentId;
        $this->position = $position;
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
        if (!$ancestor instanceof EntityInterface) {
            if ($ancestor === null) {
                return 0;
            } else {
                return static::find($ancestor)->real_depth + 1;
            }
        } else {
            return $ancestor->real_depth + 1;
        }
    }

    /**
     * Perform a model insert operation.
     *
     * @param  EloquentBuilder $query
     * @param  array $options
     *
     * @return bool
     */
    protected function performInsert(Builder $query, array $options = [])
    {
        if ($this->isMoved === false) {
            $this->position = $this->position !== null ? $this->position : $this->getLatestPosition();
            $this->real_depth = $this->getNewRealDepth($this->parent_id);
        }

        return parent::performInsert($query, $options);
    }

    /**
     * Perform a model update operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  array $options
     *
     * @return bool
     */
    protected function performUpdate(Builder $query, array $options = [])
    {
        if (parent::performUpdate($query, $options)) {
            if ($this->real_depth != $this->previousRealDepth && $this->isMoved === true) {
                $action = ($this->real_depth > $this->previousRealDepth ? 'increment' : 'decrement');
                $amount = abs($this->real_depth - $this->previousRealDepth);

                $this->subqueryClosureBy('descendant')->$action($this->getRealDepthColumn(), $amount);
            }

            return true;
        }

        return false;
    }

    /**
     * Gets the next sibling position after the last one.
     *
     * @return int
     */
    private function getLatestPosition()
    {
        $positionColumn = $this->getPositionColumn();
        $parentIdColumn = $this->getParentIdColumn();

        $entity = $this->select($positionColumn)
            ->where($parentIdColumn, '=', $this->parent_id)
            ->latest($positionColumn)
            ->first();

        $position = $entity !== null ? $entity->position : -1;

        return $position + 1;
    }

    /**
     * Reorders model's siblings when one is moved to another position or ancestor.
     *
     * @param bool $parentIdChanged
     * @return void
     */
    protected function reorderSiblings($parentIdChanged = false)
    {
        list($range, $action) = $this->setupReordering($parentIdChanged);

        $positionColumn = $this->getPositionColumn();

        // As the method called twice (before moving and after moving),
        // first we gather "old" siblings by the old parent id value of the model.
        if ($parentIdChanged === true) {
            $query = $this->siblingsQuery(false, $this->previousParentId);
        } else {
            $query = $this->siblingsQuery();
        }

        if ($action) {
            $query->buildWherePosition($positionColumn, $range)
                ->where($this->getKeyName(), '<>', $this->getKey())
                ->$action($positionColumn);
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
        $range = $action = null;
        // If the model's parent was changed, firstly we decrement
        // positions of the 'old' next siblings of the model.
        if ($parentIdChanged === true) {
            $range = $this->previousPosition;
            $action = 'decrement';
        } else {
            // TODO: There's probably a bug here where if you just created an entity and you set it to be
            // a root (parent_id = null) then it comes in here (while it should have gone in the else)
            // Reordering within the same ancestor
            if ($this->previousParentId !== false && $this->previousParentId == $this->parent_id) {
                if ($this->position > $this->previousPosition) {
                    $range = [$this->previousPosition, $this->position];
                    $action = 'decrement';
                } else if ($this->position < $this->previousPosition) {
                    $range = [$this->position, $this->previousPosition];
                    $action = 'increment';
                }
            } // Ancestor has changed
            else {
                $range = $this->position;
                $action = 'increment';
            }
        }

        if (!is_array($range)) {
            $range = [$range, null];
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
     * Moves node to another ancestor.
     *
     * @return void
     */
    protected function moveNode()
    {
        if ($this->exists) {
            if ($this->closure->ancestor === null) {
                $primaryKey = $this->getKey();
                $this->closure->ancestor = $primaryKey;
                $this->closure->descendant = $primaryKey;
                $this->closure->depth = 0;
            }

            if ($this->isDirty($this->getParentIdColumn())) {
                $this->reorderSiblings(true);
                $this->closure->moveNodeTo($this->parent_id);
            }
        }
    }

    /**
     * Clamp the position between 0 and the last position of the current parent.
     */
    protected function clampPosition()
    {
        if (!$this->isDirty($this->getPositionColumn())) {
            return;
        }
        $newPosition = max(0, min($this->position, $this->getLatestPosition()));
        $this->attributes[$this->getPositionColumn()] = $newPosition;
    }

    /**
     * Deletes a subtree from database.
     *
     * @param bool $withSelf
     * @param bool $forceDelete
     * @return void
     */
    public function deleteSubtree($withSelf = false, $forceDelete = false)
    {
        $action = ($forceDelete === true ? 'forceDelete' : 'delete');

        $ids = $this->joinClosureBy('descendant', $withSelf)->pluck($this->getKeyName());

        if ($forceDelete) {
            $this->closure->whereIn($this->closure->getDescendantColumn(), $ids)->delete();
        }

        $this->whereIn($this->getKeyName(), $ids)->$action();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return QueryBuilder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();
        $grammar = $conn->getQueryGrammar();

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
    }
}
