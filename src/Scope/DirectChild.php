<?php

declare(strict_types=1);

namespace Franzose\ClosureTable\Scope;

use Illuminate\Database\Eloquent\Builder;

/**
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
 */
trait DirectChild
{
    /**
     * Returns query builder for node's children.
     */
    public function scopeChildNode(Builder $builder): Builder
    {
        return $this->scopeChildNodeOf($builder, $this->getKey());
    }

    /**
     * Returns query builder for children of the node with the given ID.
     */
    public function scopeChildNodeOf(Builder $builder, mixed $id): Builder
    {
        $parentId = $this->getParentIdColumn();

        return $builder
            ->whereNotNull($parentId)
            ->where($parentId, '=', $id);
    }

    /**
     * Returns query builder for node's child at the given position.
     */
    public function scopeChildAt(Builder $builder, int $position): Builder
    {
        return $this
            ->scopeChildNode($builder)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for child of the node with the given ID at the given position.
     */
    public function scopeChildOf(Builder $builder, mixed $id, int $position): Builder
    {
        return $this
            ->scopeChildNodeOf($builder, $id)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for the first child of a node.
     */
    public function scopeFirstChild(Builder $builder): Builder
    {
        return $this->scopeChildAt($builder, 0);
    }

    /**
     * Returns query builder for the first child of the node with the given ID.
     */
    public function scopeFirstChildOf(Builder $builder, mixed $id): Builder
    {
        return $this->scopeChildOf($builder, $id, 0);
    }

    /**
     * Returns query builder for the last child of a node.
     */
    public function scopeLastChild(Builder $builder): Builder
    {
        return $this->scopeChildNode($builder)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Returns query builder for the last child of the node with the given ID.
     */
    public function scopeLastChildOf(Builder $builder, mixed $id): Builder
    {
        return $this->scopeChildNodeOf($builder, $id)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Returns query builder for a range of children.
     */
    public function scopeChildrenRange(Builder $builder, int $from, ?int $to = null): Builder
    {
        $position = $this->getPositionColumn();
        $query = $this->scopeChildNode($builder)->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Returns query builder for a range of children of the node with the given ID.
     */
    public function scopeChildrenRangeOf(Builder $builder, mixed $id, int $from, ?int $to = null): Builder
    {
        $position = $this->getPositionColumn();
        $query = $this->scopeChildNodeOf($builder, $id)->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }
}
