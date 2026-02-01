<?php

declare(strict_types=1);

namespace Franzose\ClosureTable\Scope;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method Builder ancestors()
 * @method Builder ancestorsOf($id)
 * @method Builder ancestorsWithSelf()
 * @method Builder ancestorsWithSelfOf($id)
 */
trait Ancestor
{
    /**
     * Returns query builder for ancestors.
     */
    public function scopeAncestors(Builder $builder): Builder
    {
        return $this->buildAncestorsQuery($builder, $this->getKey(), false);
    }

    /**
     * Returns query builder for ancestors of the node with the given ID.
     */
    public function scopeAncestorsOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildAncestorsQuery($builder, $id, false);
    }

    /**
     * Returns query builder for ancestors including the current node.
     */
    public function scopeAncestorsWithSelf(Builder $builder): Builder
    {
        return $this->buildAncestorsQuery($builder, $this->getKey(), true);
    }

    /**
     * Returns query builder for ancestors of the node with given ID including that node also.
     */
    public function scopeAncestorsWithSelfOf(Builder $builder, mixed $id): Builder
    {
        return $this->buildAncestorsQuery($builder, $id, true);
    }

    /**
     * Builds base ancestors query.
     */
    private function buildAncestorsQuery(Builder $builder, mixed $id, bool $withSelf): Builder
    {
        $depthOperator = $withSelf ? '>=' : '>';

        return $builder
            ->join(
                $this->closure->getTable(),
                $this->closure->getQualifiedAncestorColumn(),
                '=',
                $this->getQualifiedKeyName()
            )
            ->where($this->closure->getQualifiedDescendantColumn(), '=', $id)
            ->where($this->closure->getQualifiedDepthColumn(), $depthOperator, 0);
    }
}
