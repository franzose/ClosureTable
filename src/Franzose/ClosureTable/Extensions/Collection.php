<?php namespace Franzose\ClosureTable\Extensions;

use \Franzose\ClosureTable\Contracts\EntityInterface;
use \Franzose\ClosureTable\Contracts\ClosureTableInterface;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Collection extends EloquentCollection {

    public function toTree($parentId = null)
    {
        $items = $this->items;

        return new static($this->makeTree($items, $parentId));
    }

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
                    $item->setRelation('children', new static($children));
                }

                $tree[] = $item;
                unset($items[$idx]);
            }


        }

        return $tree;
    }
}
