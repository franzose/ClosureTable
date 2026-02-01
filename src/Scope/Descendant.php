<?php

declare(strict_types=1);

namespace Franzose\ClosureTable\Scope;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method Builder descendants()
 * @method Builder descendantsOf($id)
 * @method Builder descendantsWithSelf()
 * @method Builder descendantsWithSelfOf($id)
 */
trait Descendant
{
    /**
     * Returns query builder for descendants.
     */
    public function scopeDescendants(Builder $builder): Builder
    {
        return $this->buildDescendantsQuery($builder, $this->getKey(), false);
    }

    /**
     * Returns query builder for descendants of the node with the given ID.
     */
    public function scopeDescendantsOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildDescendantsQuery($builder, $id, false);
    }

    /**
     * Returns query builder for descendants including the current node.
     */
    public function scopeDescendantsWithSelf(Builder $builder): Builder
    {
        return $this->buildDescendantsQuery($builder, $this->getKey(), true);
    }

    /**
     * Returns query builder for descendants of the node with the given ID including that node also.
     */
    public function scopeDescendantsWithSelfOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildDescendantsQuery($builder, $id, true);
    }

    /**
     * Builds base descendants query.
     */
    private function buildDescendantsQuery(Builder $builder, mixed $id, bool $withSelf): Builder
    {
        $depthOperator = $withSelf ? '>=' : '>';

        return $builder
            ->join(
                $this->closure->getTable(),
                $this->closure->getQualifiedDescendantColumn(),
                '=',
                $this->getQualifiedKeyName()
            )
            ->where($this->closure->getQualifiedAncestorColumn(), '=', $id)
            ->where($this->closure->getQualifiedDepthColumn(), $depthOperator, 0);
    }
}
