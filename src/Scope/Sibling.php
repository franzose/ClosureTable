<?php

declare(strict_types=1);

namespace Franzose\ClosureTable\Scope;

use Illuminate\Database\Eloquent\Builder;

/**
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
 */
trait Sibling
{
    /**
     * Returns query builder for siblings.
     */
    public function scopeSibling(Builder $builder): Builder
    {
        $parentIdColumn = $this->getParentIdColumn();

        if ($this->parent_id === null) {
            return $builder->whereNull($parentIdColumn);
        }

        return $builder->where($parentIdColumn, '=', $this->parent_id);
    }

    /**
     * Returns query builder for siblings of the node with the given ID.
     */
    public function scopeSiblingOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildSiblingQuery($builder, $id);
    }

    /**
     * Returns query builder for siblings excluding the current node.
     */
    public function scopeSiblings(Builder $builder): Builder
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getKeyName(), '<>', $this->getKey());
    }

    /**
     * Returns query builder for siblings of the node with given ID excluding that node.
     */
    public function scopeSiblingsOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildSiblingQuery($builder, $id, fn(int $position) => fn(Builder $builder) => $builder->where($this->getPositionColumn(), '<>', $position));
    }

    /**
     * Returns query builder for neighbors (siblings with position +/-1).
     */
    public function scopeNeighbors(Builder $builder): Builder
    {
        $position = $this->position;

        return $this
            ->scopeSiblings($builder)
            ->whereIn($this->getPositionColumn(), [$position - 1, $position + 1]);
    }

    /**
     * Returns query builder for neighbors of the node with the given ID.
     */
    public function scopeNeighborsOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildSiblingQuery($builder, $id, fn(int $position) => fn(Builder $builder) => $builder->whereIn($this->getPositionColumn(), [$position - 1, $position + 1]));
    }

    /**
     * Returns query builder for siblings at given position.
     */
    public function scopeSiblingAt(Builder $builder, int $position): Builder
    {
        return $this
            ->scopeSiblings($builder)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for siblings of the node with the given ID at the given position.
     */
    public function scopeSiblingOfAt(Builder $builder, mixed $id, int $position): Builder
    {
        return $this
            ->scopeSiblingOf($builder, $id)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for the first sibling of a node.
     */
    public function scopeFirstSibling(Builder $builder): Builder
    {
        return $this->scopeSiblingAt($builder, 0);
    }

    /**
     * Returns query builder for the first sibling of the node with the given ID.
     */
    public function scopeFirstSiblingOf(Builder $builder, mixed $id): Builder
    {
        return $this->scopeSiblingOfAt($builder, $id, 0);
    }

    /**
     * Returns query builder for the last sibling of a node.
     */
    public function scopeLastSibling(Builder $builder): Builder
    {
        return $this->scopeSiblings($builder)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Returns query builder for the last sibling of the node with the given ID.
     */
    public function scopeLastSiblingOf(Builder $builder, mixed $id): Builder
    {
        return $this
            ->scopeSiblingOf($builder, $id)
            ->orderByDesc($this->getPositionColumn())
            ->limit(1);
    }

    /**
     * Returns query builder for the previous sibling of a node.
     */
    public function scopePrevSibling(Builder $builder): Builder
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '=', $this->position - 1);
    }

    /**
     * Returns query builder for the previous sibling of the node with the given ID.
     */
    public function scopePrevSiblingOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildSiblingQuery($builder, $id, fn(int $position) => fn(Builder $builder) => $builder->where($this->getPositionColumn(), '=', $position - 1));
    }

    /**
     * Returns query builder for the previous siblings of a node.
     */
    public function scopePrevSiblings(Builder $builder): Builder
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '<', $this->position);
    }

    /**
     * Returns query builder for the previous siblings of the node with the given ID.
     */
    public function scopePrevSiblingsOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildSiblingQuery($builder, $id, fn(int $position) => fn(Builder $builder) => $builder->where($this->getPositionColumn(), '<', $position));
    }

    /**
     * Returns query builder for the next sibling of a node.
     */
    public function scopeNextSibling(Builder $builder): Builder
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '=', $this->position + 1);
    }

    /**
     * Returns query builder for the next sibling of the node with the given ID.
     */
    public function scopeNextSiblingOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildSiblingQuery($builder, $id, fn(int $position) => fn(Builder $builder) => $builder->where($this->getPositionColumn(), '=', $position + 1));
    }

    /**
     * Returns query builder for the next siblings of a node.
     */
    public function scopeNextSiblings(Builder $builder): Builder
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '>', $this->position);
    }

    /**
     * Returns query builder for the next siblings of the node with the given ID.
     */
    public function scopeNextSiblingsOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildSiblingQuery($builder, $id, fn(int $position) => fn(Builder $builder) => $builder->where($this->getPositionColumn(), '>', $position));
    }

    /**
     * Returns query builder for siblings in the given range.
     */
    public function scopeSiblingsRange(Builder $builder, int $from, ?int $to = null): Builder
    {
        $query = $this
            ->scopeSiblings($builder)
            ->where($this->getPositionColumn(), '>=', $from);

        if ($to !== null) {
            $query->where($this->getPositionColumn(), '<=', $to);
        }

        return $query;
    }

    /**
     * Returns query builder for siblings of the node with the given ID in the given range.
     */
    public function scopeSiblingsRangeOf(Builder $builder, mixed $id, int $from, ?int $to = null): Builder
    {
        $query = $this
            ->scopeSiblingsOf($builder, $id)
            ->where($this->getPositionColumn(), '>=', $from);

        if ($to !== null) {
            $query->where($this->getPositionColumn(), '<=', $to);
        }

        return $query;
    }

    /**
     * Builds query for siblings.
     */
    private function buildSiblingQuery(Builder $builder, mixed $id, ?callable $positionCallback = null): Builder
    {
        $parentIdColumn = $this->getParentIdColumn();
        $positionColumn = $this->getPositionColumn();

        /** @var static|null $entity */
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
}
