<?php
namespace Franzose\ClosureTable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Franzose\ClosureTable\Contracts\EntityInterface;
use Franzose\ClosureTable\Extensions\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * Basic entity class.
 *
 * Properties, listed below, are used to make the internal code cleaner.
 * However, if you named, for example, the position column to be "pos",
 * remember you can get its value either by $this->pos or $this->position.
 *
 * @property int position Alias for the current position attribute name
 * @property int parent_id Alias for the direct ancestor identifier attribute name
 * @property Collection children Child nodes loaded from the database
 * @method Builder ancestors()
 * @method Builder ancestorsOf($id)
 * @method Builder ancestorsWithSelf()
 * @method Builder ancestorsWithSelfOf($id)
 * @method Builder descendants()
 * @method Builder descendantsOf($id)
 * @method Builder descendantsWithSelf()
 * @method Builder descendantsWithSelfOf($id)
 * @method Builder childNode()
 * @method Builder childNodeOf($id)
 * @method Builder childAt(int $position)
 * @method Builder childOf($id, int $position)
 * @method Builder firstChild()
 * @method Builder firstChildOf($id)
 * @method Builder lastChild()
 * @method Builder lastChildOf($id)
 * @method Builder childrenRange(int $from, int $to = null)
 * @method Builder childrenRangeOf($id, int $from, int $to = null)
 * @method Builder sibling()
 * @method Builder siblingOf($id)
 * @method Builder siblings()
 * @method Builder siblingsOf($id)
 * @method Builder neighbors()
 * @method Builder neighborsOf($id)
 * @method Builder siblingAt(int $position)
 * @method Builder siblingOfAt($id, int $position)
 * @method Builder firstSibling()
 * @method Builder firstSiblingOf($id)
 * @method Builder lastSibling()
 * @method Builder lastSiblingOf($id)
 * @method Builder prevSibling()
 * @method Builder prevSiblingOf($id)
 * @method Builder prevSiblings()
 * @method Builder prevSiblingsOf($id)
 * @method Builder nextSibling()
 * @method Builder nextSiblingOf($id)
 * @method Builder nextSiblings()
 * @method Builder nextSiblingsOf($id)
 * @method Builder siblingsRange(int $from, int $to = null)
 * @method Builder siblingsRangeOf($id, int $from, int $to = null)
 *
 * @package Franzose\ClosureTable
 */
class Entity extends Eloquent implements EntityInterface
{
    const CHILDREN_RELATION_NAME = 'children';

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
     * Whether this node is being moved to another parent node.
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

    /**
     * Entity constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $position = $this->getPositionColumn();

        $this->fillable(array_merge($this->getFillable(), [$position]));

        if (isset($attributes[$position]) && $attributes[$position] < 0) {
            $attributes[$position] = 0;
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

        $parentId = $this->getParentIdColumn();
        $this->previousParentId = $this->original[$parentId] ?? null;
        $this->attributes[$parentId] = $value;
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

        $position = $this->getPositionColumn();
        $this->previousPosition = $this->original[$position] ?? null;
        $this->attributes[$position] = max(0, (int) $value);
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
     * @deprecated since 6.0
     */
    public function getRealDepthColumn()
    {
        return 'real_depth';
    }

    /**
     * Gets the "children" relation index.
     *
     * @return string
     * @deprecated since 6.0
     */
    public function getChildrenRelationIndex()
    {
        return static::CHILDREN_RELATION_NAME;
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::saving(static function (Entity $entity) {
            if ($entity->isDirty($entity->getPositionColumn())) {
                $latest = static::getLatestPosition($entity);

                if (!$entity->isMoved) {
                    $latest--;
                }

                $entity->position = max(0, min($entity->position, $latest));
            } elseif (!$entity->exists) {
                $entity->position = static::getLatestPosition($entity);
            }
        });

        // When entity is created, the appropriate
        // data will be put into the closure table.
        static::created(static function (Entity $entity) {
            $entity->previousParentId = null;
            $entity->previousPosition = null;

            $descendant = $entity->getKey();
            $ancestor = $entity->parent_id ?? $descendant;

            $entity->closure->insertNode($ancestor, $descendant);
        });

        static::saved(static function (Entity $entity) {
            $parentIdChanged = $entity->isDirty($entity->getParentIdColumn());

            if ($parentIdChanged || $entity->isDirty($entity->getPositionColumn())) {
                $entity->reorderSiblings();
            }

            if ($entity->closure->ancestor === null) {
                $primaryKey = $entity->getKey();
                $entity->closure->ancestor = $primaryKey;
                $entity->closure->descendant = $primaryKey;
                $entity->closure->depth = 0;
            }

            if ($parentIdChanged) {
                $entity->closure->moveNodeTo($entity->parent_id);
            }
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
     * Returns many-to-one relationship to the direct ancestor.
     *
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), $this->getParentIdColumn());
    }

    /**
     * Returns query builder for ancestors.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeAncestors(Builder $builder)
    {
        return $this->buildAncestorsQuery($builder, $this->getKey(), false);
    }

    /**
     * Returns query builder for ancestors of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeAncestorsOf(Builder $builder, $id)
    {
        return $this->buildAncestorsQuery($builder, $id, false);
    }

    /**
     * Returns query builder for ancestors including the current node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeAncestorsWithSelf(Builder $builder)
    {
        return $this->buildAncestorsQuery($builder, $this->getKey(), true);
    }

    /**
     * Returns query builder for ancestors of the node with given ID including that node also.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeAncestorsWithSelfOf(Builder $builder, $id)
    {
        return $this->buildAncestorsQuery($builder, $id, true);
    }

    /**
     * Builds base ancestors query.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param bool $withSelf
     *
     * @return Builder
     */
    private function buildAncestorsQuery(Builder $builder, $id, $withSelf)
    {
        $depthOperator = $withSelf ? '>=' : '>';

        return $builder
            ->join(
                $this->closure->getTable(),
                $this->closure->getAncestorColumn(),
                '=',
                $this->getQualifiedKeyName()
            )
            ->where($this->closure->getDescendantColumn(), '=', $id)
            ->where($this->closure->getDepthColumn(), $depthOperator, 0);
    }

    /**
     * Retrieves all ancestors of a model.
     *
     * @param array $columns
     * @return Collection
     */
    public function getAncestors(array $columns = ['*'])
    {
        return $this->ancestors()->get($columns);
    }

    /**
     * Retrieves tree structured ancestors of a model.
     *
     * @param array $columns
     * @return Collection
     * @deprecated since 6.0, use {@link Collection::toTree()} instead
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
     * @deprecated since 6.0, use {@link Entity::ancestors()} scope instead
     */
    public function getAncestorsWhere($column, $operator = null, $value = null, array $columns = ['*'])
    {
        return $this->ancestors()->where($column, $operator, $value)->get($columns);
    }

    /**
     * Returns a number of model's ancestors.
     *
     * @return int
     */
    public function countAncestors()
    {
        return $this->ancestors()->count();
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
     * Returns query builder for descendants.
     *
     * @param Builder $builder
     * @param bool $withSelf
     *
     * @return Builder
     */
    public function scopeDescendants(Builder $builder)
    {
        return $this->buildDescendantsQuery($builder, $this->getKey(), false);
    }

    /**
     * Returns query builder for descendants of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeDescendantsOf(Builder $builder, $id)
    {
        return $this->buildDescendantsQuery($builder, $id, false);
    }

    /**
     * Returns query builder for descendants including the current node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeDescendantsWithSelf(Builder $builder)
    {
        return $this->buildDescendantsQuery($builder, $this->getKey(), true);
    }

    /**
     * Returns query builder for descendants including the current node of the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeDescendantsWithSelfOf(Builder $builder, $id)
    {
        return $this->buildDescendantsQuery($builder, $id, true);
    }

    /**
     * Builds base descendants query.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param bool $withSelf
     *
     * @return Builder
     */
    private function buildDescendantsQuery(Builder $builder, $id, $withSelf)
    {
        $depthOperator = $withSelf ? '>=' : '>';

        return $builder
            ->join(
                $this->closure->getTable(),
                $this->closure->getDescendantColumn(),
                '=',
                $this->getQualifiedKeyName()
            )
            ->where($this->closure->getAncestorColumn(), '=', $id)
            ->where($this->closure->getDepthColumn(), $depthOperator, 0);
    }

    /**
     * Retrieves all descendants of a model.
     *
     * @param array $columns
     * @return Collection
     */
    public function getDescendants(array $columns = ['*'])
    {
        return $this->descendants()->get($columns);
    }

    /**
     * Retrieves tree structured descendants of a model.
     *
     * @param array $columns
     * @return Collection
     * @deprecated since 6.0, use {@link Collection::toTree()} instead
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
     * @deprecated since 6.0, use {@link Entity::descendants()} scope instead
     */
    public function getDescendantsWhere($column, $operator = null, $value = null, array $columns = ['*'])
    {
        return $this->descendants()->where($column, $operator, $value)->get($columns);
    }

    /**
     * Returns a number of model's descendants.
     *
     * @return int
     */
    public function countDescendants()
    {
        return $this->descendants()->count();
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
     * Returns query builder for child nodes.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeChildNode(Builder $builder)
    {
        return $this->scopeChildNodeOf($builder, $this->getKey());
    }

    /**
     * Returns query builder for child nodes of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeChildNodeOf(Builder $builder, $id)
    {
        $parentId = $this->getParentIdColumn();

        return $builder
            ->whereNotNull($parentId)
            ->where($parentId, '=', $id);
    }

    /**
     * Returns query builder for a child at the given position.
     *
     * @param Builder $builder
     * @param int $position
     *
     * @return Builder
     */
    public function scopeChildAt(Builder $builder, $position)
    {
        return $this
            ->scopeChildNode($builder)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for a child at the given position of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $position
     *
     * @return Builder
     */
    public function scopeChildOf(Builder $builder, $id, $position)
    {
        return $this
            ->scopeChildNodeOf($builder, $id)
            ->where($this->getPositionColumn(), '=', $position);
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
     * Returns query builder for the first child node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeFirstChild(Builder $builder)
    {
        return $this->scopeChildAt($builder, 0);
    }

    /**
     * Returns query builder for the first child node of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeFirstChildOf(Builder $builder, $id)
    {
        return $this->scopeChildOf($builder, $id, 0);
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
     * Returns query builder for the last child node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeLastChild(Builder $builder)
    {
        return $this->scopeChildNode($builder)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Returns query builder for the last child node of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeLastChildOf(Builder $builder, $id)
    {
        return $this->scopeChildNodeOf($builder, $id)->orderByDesc($this->getPositionColumn());
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
     * Returns query builder to child nodes in the range of the given positions.
     *
     * @param Builder $builder
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeChildrenRange(Builder $builder, $from, $to = null)
    {
        $position = $this->getPositionColumn();
        $query = $this->scopeChildNode($builder)->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Returns query builder to child nodes in the range of the given positions for the node of the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeChildrenRangeOf(Builder $builder, $id, $from, $to = null)
    {
        $position = $this->getPositionColumn();
        $query = $this->scopeChildNodeOf($builder, $id)->where($position, '>=', $from);

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
            $position = $position ?? $this->getLatestChildPosition();

            $child->moveTo($position, $this);
        }

        return $returnChild === true ? $child : $this;
    }

    /**
     * Returns the latest child position.
     *
     * @return int
     */
    private function getLatestChildPosition()
    {
        $lastChild = $this->lastChild()->first([$this->getPositionColumn()]);

        return $lastChild !== null ? $lastChild->position + 1 : 0;
    }

    /**
     * Appends a collection of children to the model.
     *
     * @param Entity[] $children
     * @param int $from
     *
     * @return Entity
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function addChildren(array $children, $from = null)
    {
        if (!$this->exists) {
            return $this;
        }

        $this->transactional(function () use (&$from, $children) {
            foreach ($children as $child) {
                $this->addChild($child, $from);
                $from++;
            }
        });

        return $this;
    }

    /**
     * Appends the given entity to the children relation.
     *
     * @param Entity $entity
     * @internal
     */
    public function appendChild(Entity $entity)
    {
        $this->getChildrenRelation()->add($entity);
    }

    /**
     * @return Collection
     */
    private function getChildrenRelation()
    {
        if (!$this->relationLoaded(static::CHILDREN_RELATION_NAME)) {
            $this->setRelation(static::CHILDREN_RELATION_NAME, new Collection());
        }

        return $this->getRelation(static::CHILDREN_RELATION_NAME);
    }

    /**
     * Removes a model's child with given position.
     *
     * @param int $position
     * @param bool $forceDelete
     *
     * @return $this
     * @throws \Throwable
     */
    public function removeChild($position = null, $forceDelete = false)
    {
        if (!$this->exists) {
            return $this;
        }

        $child = $this->getChildAt($position, [
            $this->getKeyName(),
            $this->getParentIdColumn(),
            $this->getPositionColumn()
        ]);

        if ($child === null) {
            return $this;
        }

        $this->transactional(function () use ($child, $forceDelete) {
            $action = ($forceDelete === true ? 'forceDelete' : 'delete');

            $child->{$action}();

            $child->nextSiblings()->decrement($this->getPositionColumn());
        });

        return $this;
    }

    /**
     * Removes model's children within a range of positions.
     *
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     *
     * @return $this
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function removeChildren($from, $to = null, $forceDelete = false)
    {
        if (!is_numeric($from) || ($to !== null && !is_numeric($to))) {
            throw new InvalidArgumentException('`from` and `to` are the position boundaries. They must be of type int.');
        }

        if (!$this->exists) {
            return $this;
        }

        $this->transactional(function () use ($from, $to, $forceDelete) {
            $action = ($forceDelete === true ? 'forceDelete' : 'delete');

            $this->childrenRange($from, $to)->{$action}();

            if ($to !== null) {
                $this
                    ->childrenRange($to)
                    ->decrement($this->getPositionColumn(), $to - $from + 1);
            }
        });

        return $this;
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
     * Returns query builder for siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeSiblingOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id);
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
     * Return query builder for siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeSiblingsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                $builder->where($this->getPositionColumn(), '<>', $position);
            };
        });
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
            ->scopeSiblings($builder)
            ->whereIn($this->getPositionColumn(), [$position - 1, $position + 1]);
    }

    /**
     * Returns query builder for the neighbors of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeNeighborsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->whereIn($this->getPositionColumn(), [$position - 1, $position + 1]);
            };
        });
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
            ->scopeSiblings($builder)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for a sibling at the given position of a node of the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $position
     *
     * @return Builder
     */
    public function scopeSiblingOfAt(Builder $builder, $id, $position)
    {
        return $this
            ->scopeSiblingOf($builder, $id)
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
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeFirstSibling(Builder $builder)
    {
        return $this->scopeSiblingAt($builder, 0);
    }

    /**
     * Returns query builder for the first sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeFirstSiblingOf(Builder $builder, $id)
    {
        return $this->scopeSiblingOfAt($builder, $id, 0);
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
        return $this->scopeSiblings($builder)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Returns query builder for the last sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeLastSiblingOf(Builder $builder, $id)
    {
        return $this
            ->scopeSiblingOf($builder, $id)
            ->orderByDesc($this->getPositionColumn())
            ->limit(1);
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
     * Returns query builder for the previous sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopePrevSiblingOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '=', $position - 1);
            };
        });
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
     * Returns query builder for the previous siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopePrevSiblingsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '<', $position);
            };
        });
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
     * Returns query builder for the next sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeNextSiblingOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '=', $position + 1);
            };
        });
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
     * Returns query builder for the next siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeNextSiblingsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '>', $position);
            };
        });
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

        if ($to !== null) {
           $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Returns query builder for a range of siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeSiblingsRangeOf(Builder $builder, $id, $from, $to = null)
    {
        $position = $this->getPositionColumn();

        $query = $this
            ->buildSiblingQuery($builder, $id)
            ->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
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
     * Builds query for siblings.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param callable|null $positionCallback
     *
     * @return Builder
     */
    private function buildSiblingQuery(Builder $builder, $id, callable $positionCallback = null)
    {
        $parentIdColumn = $this->getParentIdColumn();
        $positionColumn = $this->getPositionColumn();

        $entity = $this
            ->select([$this->getKeyName(), $parentIdColumn, $positionColumn])
            ->from($this->getTable())
            ->where($this->getKeyName(), '=', $id)
            ->limit(1)
            ->first();

        if ($entity === null) {
            return $builder;
        }

        if ($entity->parent_id === null) {
            $builder->whereNull($parentIdColumn);
        } else {
            $builder->where($parentIdColumn, '=', $entity->parent_id);
        }

        if (is_callable($positionCallback)) {
            $builder->where($positionCallback($entity->position));
        }

        return $builder;
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
            $position = $position ?? static::getLatestPosition($this);

            $sibling->moveTo($position, $this->parent_id);

            if ($position < $this->position) {
                $this->position++;
            }
        }

        return ($returnSibling === true ? $sibling : $this);
    }

    /**
     * Appends multiple siblings within the current depth.
     *
     * @param Entity[] $siblings
     * @param int|null $from
     *
     * @return Entity
     * @throws Throwable
     */
    public function addSiblings(array $siblings, $from = null)
    {
        if (!$this->exists) {
            return $this;
        }

        $from = $from ?? static::getLatestPosition($this);

        $this->transactional(function () use ($siblings, &$from) {
            foreach ($siblings as $sibling) {
                $this->addSibling($sibling, $from);
                $from++;
            }
        });

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
     * @deprecated since 6.0
     */
    public static function getTree(array $columns = ['*'])
    {
        /**
         * @var Entity $instance
         */
        $instance = new static;

        return $instance
            ->load(static::CHILDREN_RELATION_NAME)
            ->orderBy($instance->getParentIdColumn())
            ->orderBy($instance->getPositionColumn())
            ->get($instance->prepareTreeQueryColumns($columns))
            ->toTree();
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
     * @deprecated since 6.0
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
     * @param Builder $query
     * @param array $columns
     *
     * @return Collection
     * @deprecated since 6.0
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
     * @param EntityInterface $parent
     *
     * @return Collection
     * @throws Throwable
     */
    public static function createFromArray(array $tree, EntityInterface $parent = null)
    {
        $entities = [];

        foreach ($tree as $item) {
            $children = $item[static::CHILDREN_RELATION_NAME] ?? [];

            /**
             * @var Entity $entity
             */
            $entity = new static($item);
            $entity->parent_id = $parent ? $parent->getKey() : null;
            $entity->save();

            if ($children !== null) {
                $entity->addChildren(static::createFromArray($children, $entity)->all());
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
        $parentId = $ancestor instanceof self ? $ancestor->getKey() : $ancestor;

        if ($this->parent_id === $parentId && $this->parent_id !== null) {
            return $this;
        }

        if ($this->getKey() === $parentId) {
            throw new InvalidArgumentException('Target entity is equal to the sender.');
        }

        $this->parent_id = $parentId;
        $this->position = $position;

        $this->isMoved = true;
        $this->save();
        $this->isMoved = false;

        return $this;
    }

    /**
     * Gets the next sibling position after the last one.
     *
     * @param Entity $entity
     *
     * @return int
     */
    public static function getLatestPosition(Entity $entity)
    {
        $positionColumn = $entity->getPositionColumn();
        $parentIdColumn = $entity->getParentIdColumn();

        $latest = $entity->select($positionColumn)
            ->where($parentIdColumn, '=', $entity->parent_id)
            ->latest($positionColumn)
            ->first();

        $position = $latest !== null ? $latest->position : -1;

        return $position + 1;
    }

    /**
     * Reorders node's siblings when it is moved to another position or ancestor.
     *
     * @return void
     */
    private function reorderSiblings()
    {
        $position = $this->getPositionColumn();

        if ($this->previousPosition !== null) {
            $this
                ->where($this->getKeyName(), '<>', $this->getKey())
                ->where($this->getParentIdColumn(), '=', $this->previousParentId)
                ->where($position, '>', $this->previousPosition)
                ->decrement($position);
        }

        $this
            ->sibling()
            ->where($this->getKeyName(), '<>', $this->getKey())
            ->where($position, '>=', $this->position)
            ->increment($position);
    }

    /**
     * Deletes a subtree from database.
     *
     * @param bool $withSelf
     * @param bool $forceDelete
     *
     * @return void
     * @throws \Exception
     */
    public function deleteSubtree($withSelf = false, $forceDelete = false)
    {
        $action = ($forceDelete === true ? 'forceDelete' : 'delete');

        $query = $withSelf ? $this->descendantsWithSelf() : $this->descendants();
        $ids = $query->pluck($this->getKeyName());

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
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Executes queries within a transaction.
     *
     * @param callable $callable
     *
     * @return mixed
     * @throws Throwable
     */
    private function transactional(callable $callable)
    {
        return $this->getConnection()->transaction($callable);
    }
}
