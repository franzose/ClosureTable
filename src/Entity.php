<?php
declare(strict_types=1);

namespace Franzose\ClosureTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Franzose\ClosureTable\Scope\Ancestor;
use Franzose\ClosureTable\Scope\DirectChild;
use Franzose\ClosureTable\Scope\Descendant;
use Franzose\ClosureTable\Scope\Sibling;
use Illuminate\Support\Facades\Schema;
use Throwable;
use InvalidArgumentException;

/**
 * Basic entity class.
 *
 * Properties, listed below, are used to make the internal code cleaner.
 * However, if you named, for example, the position column to be "pos",
 * remember you can get its value either by $this->pos or $this->position.
 *
 * @property int|null position Alias for the current position attribute name
 * @property mixed parent_id Alias for the direct ancestor identifier attribute name
 * @property int|null depth Current node depth
 * @property EntityCollection children Child nodes loaded from the database
 */
class Entity extends Eloquent
{
    use SoftDeletes;
    use Ancestor;
    use Descendant;
    use DirectChild;
    use Sibling;

    private const DEPTH_RELATION_NAME = 'closureDepth';
    public const CHILDREN_RELATION_NAME = 'children';

    /**
     * ClosureTable model instance.
     */
    protected string|ClosureTable $closure = ClosureTable::class;

    /**
     * Cached "previous" (i.e. before the model is moved) direct ancestor id of this model.
     */
    private mixed $previousParentId = null;

    /**
     * Cached "previous" (i.e. before the model is moved) model position.
     */
    private ?int $previousPosition = null;

    /**
     * Cache for table/column existence checks.
     *
     * @var array<string, bool>
     */
    protected static array $tableColumnCache = [];

    /**
     * Whether this node is being moved to another parent node.
     */
    private bool $isReparenting = false;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The relations to eager load on every query.
     *
     * @var string[]
     */
    protected $with = [
        self::DEPTH_RELATION_NAME,
    ];

    /**
     * Entity constructor.
     */
    public function __construct(array $attributes = [])
    {
        $position = $this->getPositionColumn();
        $parentId = $this->getParentIdColumn();

        $this->fillable(array_merge($this->getFillable(), [$position, $parentId]));

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

    public function newFromBuilder($attributes = [], $connection = null): static
    {
        $instance = parent::newFromBuilder($attributes, $connection);
        $instance->previousParentId = $instance->parent_id;
        $instance->previousPosition = $instance->position;
        return $instance;
    }

    /**
     * Gets value of the "parent id" attribute.
     */
    public function getParentIdAttribute(): mixed
    {
        return $this->getAttributeFromArray($this->getParentIdColumn());
    }

    /**
     * Sets new parent id and caches the old one.
     */
    public function setParentIdAttribute(mixed $value): void
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
     */
    public function getQualifiedParentIdColumn(): string
    {
        return $this->getTable() . '.' . $this->getParentIdColumn();
    }

    /**
     * Gets the short name of the "parent id" column.
     */
    public function getParentIdColumn(): string
    {
        return 'parent_id';
    }

    /**
     * Gets value of the "position" attribute.
     */
    public function getPositionAttribute(): ?int
    {
        $value = $this->getAttributeFromArray($this->getPositionColumn());

        return $value === null ? null : (int) $value;
    }

    /**
     * Sets new position and caches the old one.
     */
    public function setPositionAttribute(int $value): void
    {
        if ($this->position === $value) {
            return;
        }

        $position = $this->getPositionColumn();
        $this->previousPosition = $this->original[$position] ?? null;
        $this->attributes[$position] = max(0, $value);
    }

    /**
     * Gets the fully qualified "position" column.
     */
    public function getQualifiedPositionColumn(): string
    {
        return $this->getTable() . '.' . $this->getPositionColumn();
    }

    /**
     * Gets the short name of the "position" column.
     */
    public function getPositionColumn(): string
    {
        return 'position';
    }

    /**
     * Returns the closure row with the max depth for the current node.
     *
     * @internal
     */
    public function closureDepth(): HasOne
    {
        $relatedClass = get_class($this->closure);
        $descendantColumn = $this->closure->getDescendantColumn();
        $depthColumn = $this->closure->getDepthColumn();

        $relation = $this->hasOne($relatedClass, $descendantColumn, $this->getKeyName());
        $relation->getRelated()->setTable($this->closure->getTable());

        return $relation->ofMany($depthColumn, 'max');
    }

    /**
     * Gets the current node depth.
     */
    public function getDepthAttribute(): ?int
    {
        if (!$this->exists) {
            return null;
        }

        if ($this->relationLoaded(static::DEPTH_RELATION_NAME)) {
            $depth = $this->getRelation(static::DEPTH_RELATION_NAME)?->getAttribute($this->closure->getDepthColumn());
            return $depth !== null ? (int) $depth : 0;
        }

        $depth = $this->closureDepth()->value($this->closure->getDepthColumn());

        return $depth !== null ? (int) $depth : 0;
    }

    /**
     * Checks if the current model table has the given column.
     */
    protected function hasTableColumn(string $column): bool
    {
        $connection = $this->getConnectionName() ?? 'default';
        $cacheKey = $connection . '|' . $this->getTable() . '|' . $column;

        if (array_key_exists($cacheKey, self::$tableColumnCache)) {
            return self::$tableColumnCache[$cacheKey];
        }

        if ($connection === 'default') {
            $exists = Schema::hasColumn($this->getTable(), $column);
        } else {
            $exists = Schema::connection($connection)->hasColumn($this->getTable(), $column);
        }

        self::$tableColumnCache[$cacheKey] = $exists;

        return $exists;
    }

    /**
     * The "booting" method of the model.
     */
    public static function boot(): void
    {
        parent::boot();

        static::saving(static function (Entity $entity) {
            if ($entity->isDirty($entity->getPositionColumn())) {
                $latest = static::getLatestPosition($entity);

                if (!$entity->isReparenting) {
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
     */
    public function isParent(): bool
    {
        return $this->exists && $this->hasChildren();
    }

    /**
     * Indicates whether the model has no ancestors.
     */
    public function isRoot(): bool
    {
        return $this->exists && $this->parent_id === null;
    }

    /**
     * Retrieves direct ancestor of a model.
     */
    public function getParent(array $columns = ['*']): ?self
    {
        return $this->exists ? $this->find($this->parent_id, $columns) : null;
    }

    /**
     * Returns many-to-one relationship to the direct ancestor.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(get_class($this), $this->getParentIdColumn());
    }

    /**
     * Retrieves all ancestors of a model.
     */
    public function getAncestors(array $columns = ['*']): EntityCollection
    {
        $query = $this->ancestors();

        $query->orderBy($this->closure->getQualifiedDepthColumn());

        if ($this->hasTableColumn($this->getPositionColumn())) {
            $query->orderBy($this->getQualifiedPositionColumn());
        }

        $query->orderBy($this->getQualifiedKeyName());

        return $query->get($columns);
    }

    /**
     * Returns a number of model's ancestors.
     */
    public function countAncestors(): int
    {
        return $this->ancestors()->count();
    }

    /**
     * Indicates whether a model has ancestors.
     */
    public function hasAncestors(): bool
    {
        return (bool) $this->countAncestors();
    }

    /**
     * Retrieves all descendants of a model.
     */
    public function getDescendants(array $columns = ['*']): EntityCollection
    {
        $query = $this->descendants();

        $query->orderBy($this->closure->getQualifiedDepthColumn());

        if ($this->hasTableColumn($this->getPositionColumn())) {
            $query->orderBy($this->getQualifiedPositionColumn());
        }

        $query->orderBy($this->getQualifiedKeyName());

        return $query->get($columns);
    }

    /**
     * Returns a number of model's descendants.
     */
    public function countDescendants(): int
    {
        return $this->descendants()->count();
    }

    /**
     * Indicates whether a model has descendants.
     */
    public function hasDescendants(): bool
    {
        return (bool) $this->countDescendants();
    }

    /**
     * Returns one-to-many relationship to child nodes.
     */
    public function children(): HasMany
    {
        return $this->hasMany(get_class($this), $this->getParentIdColumn());
    }

    /**
     * Retrieves all children of a model.
     */
    public function getChildren(array $columns = ['*']): EntityCollection
    {
        return $this->children()->get($columns);
    }

    /**
     * Returns a number of model's children.
     */
    public function countChildren(): int
    {
        return $this->children()->count();
    }

    /**
     * Indicates whether a model has children.
     */
    public function hasChildren(): bool
    {
        return (bool) $this->countChildren();
    }

    /**
     * Retrieves a child with given position.
     */
    public function getChildAt(int $position, array $columns = ['*']): ?self
    {
        return $this->childAt($position)->first($columns);
    }

    /**
     * Retrieves the first child.
     */
    public function getFirstChild(array $columns = ['*']): ?self
    {
        return $this->getChildAt(0, $columns);
    }


    /**
     * Retrieves the last child.
     */
    public function getLastChild(array $columns = ['*']): ?self
    {
        return $this->lastChild()->first($columns);
    }


    /**
     * Retrieves children within given positions range.
     */
    public function getChildrenRange(int $from, ?int $to = null, array $columns = ['*']): EntityCollection
    {
        return $this->childrenRange($from, $to)->get($columns);
    }

    /**
     * Appends a child to the model.
     */
    public function addChild(self $child, ?int $position = null, bool $returnChild = false): static
    {
        if ($this->exists) {
            $position = $position ?? $this->getLatestChildPosition();

            $child->moveTo($position, $this);
        }

        return $returnChild === true ? $child : $this;
    }

    /**
     * Returns the latest child position.
     */
    private function getLatestChildPosition(): int
    {
        $lastChild = $this->lastChild()->first([$this->getPositionColumn()]);

        return $lastChild !== null ? $lastChild->position + 1 : 0;
    }

    /**
     * Appends a collection of children to the model.
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function addChildren(array $children, ?int $from = null): static
    {
        if (!$this->exists) {
            return $this;
        }

        if ($from === null) {
            $from = $this->getLatestChildPosition();
        }

        $this->transactional(function () use (&$from, $children) {
            foreach ($children as $child) {
                $this->addChild($child, $from);
                $from++;
            }
        });

        return $this;
    }

    private function getChildrenRelation(): EntityCollection
    {
        if (!$this->relationLoaded(static::CHILDREN_RELATION_NAME)) {
            $this->setRelation(static::CHILDREN_RELATION_NAME, new EntityCollection());
        }

        return $this->getRelation(static::CHILDREN_RELATION_NAME);
    }

    /**
     * Removes a model's child with given position.
     *
     * @throws Throwable
     */
    public function removeChild(?int $position = null, bool $forceDelete = false): static
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
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function removeChildren(int $from, ?int $to = null, bool $forceDelete = false): static
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
     * Retrieves all siblings of a model.
     */
    public function getSiblings(array $columns = ['*']): EntityCollection
    {
        return $this->siblings()->get($columns);
    }

    /**
     * Returns number of model's siblings.
     */
    public function countSiblings(): int
    {
        return $this->siblings()->count();
    }

    /**
     * Indicates whether a model has siblings.
     */
    public function hasSiblings(): bool
    {
        return (bool) $this->countSiblings();
    }

    /**
     * Retrieves neighbors (immediate previous and immediate next models) of a model.
     */
    public function getNeighbors(array $columns = ['*']): EntityCollection
    {
        return $this->neighbors()->get($columns);
    }

    /**
     * Retrieves a model's sibling with given position.
     */
    public function getSiblingAt(int $position, array $columns = ['*']): ?self
    {
        return $this->siblingAt($position)->first($columns);
    }

    /**
     * Retrieves the first model's sibling.
     */
    public function getFirstSibling(array $columns = ['*']): ?self
    {
        return $this->getSiblingAt(0, $columns);
    }


    /**
     * Retrieves the last model's sibling.
     */
    public function getLastSibling(array $columns = ['*']): ?self
    {
        return $this->lastSibling()->first($columns);
    }


    /**
     * Retrieves immediate previous sibling of a model.
     */
    public function getPrevSibling(array $columns = ['*']): ?self
    {
        return $this->prevSibling()->first($columns);
    }


    /**
     * Retrieves all previous siblings of a model.
     */
    public function getPrevSiblings(array $columns = ['*']): EntityCollection
    {
        return $this->prevSiblings()->get($columns);
    }

    /**
     * Returns number of previous siblings of a model.
     */
    public function countPrevSiblings(): int
    {
        return $this->prevSiblings()->count();
    }

    /**
     * Indicates whether a model has previous siblings.
     */
    public function hasPrevSiblings(): bool
    {
        return (bool) $this->countPrevSiblings();
    }


    /**
     * Retrieves immediate next sibling of a model.
     */
    public function getNextSibling(array $columns = ['*']): ?self
    {
        return $this->nextSibling()->first($columns);
    }


    /**
     * Retrieves all next siblings of a model.
     */
    public function getNextSiblings(array $columns = ['*']): EntityCollection
    {
        return $this->nextSiblings()->get($columns);
    }

    /**
     * Returns number of next siblings of a model.
     */
    public function countNextSiblings(): int
    {
        return $this->nextSiblings()->count();
    }

    /**
     * Indicates whether a model has next siblings.
     */
    public function hasNextSiblings(): bool
    {
        return (bool) $this->countNextSiblings();
    }


    /**
     * Retrieves siblings within given positions range.
     */
    public function getSiblingsRange(int $from, ?int $to = null, array $columns = ['*']): EntityCollection
    {
        return $this->siblingsRange($from, $to)->get($columns);
    }

    /**
     * Appends a sibling within the current depth.
     */
    public function addSibling(self $sibling, ?int $position = null, bool $returnSibling = false): static
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
     * @throws Throwable
     */
    public function addSiblings(array $siblings, ?int $from = null): static
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
     */
    public static function getRoots(array $columns = ['*']): EntityCollection
    {
        $instance = new static();

        return $instance->whereNull($instance->getParentIdColumn())->get($columns);
    }

    /**
     * Makes model a root with given position.
     */
    public function makeRoot(int $position): static
    {
        return $this->moveTo($position);
    }

    /**
     * Saves models from the given attributes array.
     *
     * @throws Throwable
     */
    public static function createFromArray(array $tree, ?self $parent = null): EntityCollection
    {
        $entities = [];

        foreach ($tree as $item) {
            $children = $item[static::CHILDREN_RELATION_NAME] ?? [];

            $entity = new static($item);
            $entity->parent_id = $parent?->getKey();
            $entity->save();

            if ($children !== null) {
                $entity->addChildren(static::createFromArray($children, $entity)->all());
            }

            $entities[] = $entity;
        }

        return new EntityCollection($entities);
    }

    /**
     * Makes the model a child or a root with given position.
     *
     * @throws InvalidArgumentException
     */
    public function moveTo(int $position, mixed $ancestor = null): static
    {
        $parentId = $ancestor instanceof self ? $ancestor->getKey() : $ancestor;

        if ($this->getKey() === $parentId) {
            throw new InvalidArgumentException('Target entity is equal to the sender.');
        }

        if ($this->exists && $parentId !== null) {
            $isDescendant = $this
                ->descendantsOf($this->getKey())
                ->where($this->getKeyName(), '=', $parentId)
                ->exists();

            if ($isDescendant) {
                throw new InvalidArgumentException('Target entity is a descendant of the sender.');
            }
        }

        $isReparenting = $this->parent_id !== $parentId;

        $this->parent_id = $parentId;
        $this->position = $position;

        $this->isReparenting = $isReparenting;
        $this->save();
        $this->isReparenting = false;

        return $this;
    }

    /**
     * Gets the next sibling position after the last one.
     */
    public static function getLatestPosition(self $entity): int
    {
        $positionColumn = $entity->getPositionColumn();
        $parentIdColumn = $entity->getParentIdColumn();

        $latest = $entity->select($positionColumn)
            ->when(
                $entity->parent_id === null,
                fn (Builder $builder) => $builder->whereNull($parentIdColumn),
                fn (Builder $builder) => $builder->where($parentIdColumn, '=', $entity->parent_id)
            )
            ->latest($positionColumn)
            ->first();

        $position = $latest !== null ? $latest->position : -1;

        return $position + 1;
    }

    /**
     * Reorders node's siblings when it is moved to another position or ancestor.
     */
    private function reorderSiblings(): void
    {
        $position = $this->getPositionColumn();
        $parentIdColumn = $this->getParentIdColumn();

        if ($this->previousPosition !== null) {
            $query = $this
                ->where($this->getKeyName(), '<>', $this->getKey())
                ->where($position, '>', $this->previousPosition);

            if ($this->previousParentId === null) {
                $query->whereNull($parentIdColumn);
            } else {
                $query->where($parentIdColumn, '=', $this->previousParentId);
            }

            $query->decrement($position);
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
     * @throws Throwable
     */
    public function deleteSubtree(bool $withSelf = false, bool $forceDelete = false): void
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
     * @param Entity[] $models
     */
    public function newCollection(array $models = []): EntityCollection
    {
        return new EntityCollection($models);
    }

    /**
     * Executes queries within a transaction.
     *
     * @throws Throwable
     */
    private function transactional(callable $callable): mixed
    {
        return $this->getConnection()->transaction($callable);
    }
}
