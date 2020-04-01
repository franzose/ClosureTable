<?php
namespace Franzose\ClosureTable\Extensions;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Franzose\ClosureTable\Models\Entity;

/**
 * Extended Collection class. Provides some useful methods.
 *
 * @package Franzose\ClosureTable\Extensions
 */
class Collection extends EloquentCollection
{
    /**
     * Retrieves children relation.
     *
     * @param $position
     * @return Collection
     */
    public function getChildrenOf($position)
    {
        if (!$this->hasChildren($position)) {
            return new static();
        }

        /** @var Entity $item */
        $item = $this->get($position);
        $relation = $item->getChildrenRelationIndex();

        return $item->getRelation($relation);
    }

    /**
     * Indicates whether an item has children.
     *
     * @param $position
     * @return bool
     */
    public function hasChildren($position)
    {
        /** @var Entity $item */
        $item = $this->get($position);
        $relation = $item->getChildrenRelationIndex();

        return array_key_exists($relation, $item->getRelations());
    }

    /**
     * Makes tree-like collection.
     *
     * @return Collection
     */
    public function toTree()
    {
        $items = $this->items;

        return new static($this->makeTree($items));
    }

    /**
     * Performs actual tree building.
     *
     * @param array $items
     * @return array
     */
    protected function makeTree(array &$items)
    {
        $result = [];
        $tops = [];

        /**
         * @var Entity $item
         */
        foreach ($items as $item) {
            $result[$item->getKey()] = $item;
        }

        foreach ($items as $item) {
            $parentId = $item->{$item->getParentIdColumn()};

            if (array_key_exists($parentId, $result)) {
                $result[$parentId]->appendRelation($item->getChildrenRelationIndex(), $item);
            } else {
                $tops[] = $item;
            }
        }

        return $tops;
    }
}
