<?php namespace Franzose\ClosureTable\Extensions;

use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Extended Collection class. Provides some useful methods.
 *
 * @package Franzose\ClosureTable\Extensions
 */
class Collection extends EloquentCollection {

    /**
     * Retrieves children relation.
     *
     * @param $position
     * @return Collection|null
     */
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

        foreach($items as $item)
        {
            $result[$item->getKey()] = $item;
        }

        foreach($items as $item)
        {
            $parentId = $item->{EntityInterface::PARENT_ID};

            if (array_key_exists($parentId, $result))
            {
                $result[$parentId]->appendRelation(EntityInterface::CHILDREN, $item);
            }
            else
            {
                $tops[] = $item;
            }
        }

        return $tops;
    }
}
