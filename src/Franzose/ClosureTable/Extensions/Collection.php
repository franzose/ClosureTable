<?php namespace Franzose\ClosureTable\Extensions;

use Franzose\ClosureTable\Contracts\EntityInterface;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Collection extends EloquentCollection {

    public function toTree()
    {
        $items = $this->items;

        return new EloquentCollection($this->makeTree($items));
    }

    protected function makeTree(array &$items, $parentId = null)
    {
        $tree = [];

        foreach($items as $item)
        {
            $itemParent = $item->parent([$item->getQualifiedKeyName()])->first();
            $itemParentId = ($itemParent instanceof EntityInterface ? $itemParent->getKey() : null);
            $itemKey = $item->getKey();

            if ($itemParentId == $parentId)
            {
                $children = $this->makeTree($items, $itemKey);

                if (count($children))
                {
                    $item->setRelation('children', new EloquentCollection($children));
                }

                $tree[] = $item;
                unset($items[$itemKey]);
            }
        }

        return $tree;
    }
}
