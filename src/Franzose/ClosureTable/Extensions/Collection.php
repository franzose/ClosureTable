<?php namespace Franzose\ClosureTable\Extensions;

use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Extended Collection class. Provides some useful methods.
 *
 * @package Franzose\ClosureTable\Extensions
 */
class Collection extends EloquentCollection {

    public function getChildrenOf($position)
    {
        if ( ! $this->hasChildren($position))
        {
            return null;
        }

        return $this->get($position)->getRelation(EntityInterface::CHILDREN);
    }

    /**
     * Indicates whether an item has children.
     *
     * @param $position
     * @return bool
     */
    public function hasChildren($position)
    {
        return array_key_exists(EntityInterface::CHILDREN, $this->get($position)->getRelations());
    }

    /**
     * Makes tree-like collection.
     *
     * @param int $parentId
     * @return Collection
     */
    public function toTree($parentId = null)
    {
        $items = $this->items;

        return new static($this->makeTree($items, $parentId));
    }

    /**
     * Performs actual tree building.
     *
     * @param array $items
     * @param int $parentId
     * @return array
     */
    protected function makeTree(array &$items, $parentId = null)
    {
        $tree = [];

        foreach($items as $idx => $item)
        {
            $itemParent = $item->getParent();
            $itemParentId = ($itemParent instanceof EntityInterface ? $itemParent->getKey() : null);
            $itemKey = $item->getKey();

            if ($itemParentId == $parentId)
            {
                $children = $this->makeTree($items, $itemKey);

                if (count($children))
                {
                    $item->setRelation(EntityInterface::CHILDREN, new static($children));
                }

                $tree[] = $item;
                unset($items[$idx]);
            }


        }

        return $tree;
    }
}
