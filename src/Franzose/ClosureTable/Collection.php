<?php namespace Franzose\ClosureTable;

class Collection extends \Illuminate\Database\Eloquent\Collection {

    public function toTree()
    {
        $tree = new Collection();
        $args = func_get_args();
        $items   = (isset($args[0]) ? $args[0] : $this->items);
        $current = null;

        if (isset($args[1]))
        {
           $current = $args[1];
        }
        elseif ($items[0]->parent() !== null)
        {
           $current = $items[0]->parent()->getKey();
        }

        foreach($items as $index => $entity)
        {
            $parent = ($entity->parent() === null ? null : $entity->parent()->getKey());

            if ($parent === $current)
            {
                unset($items[$index]);
                $tree->add($entity);
                $entity->nested = $this->toTree($items, $entity->getKey());
            }
        }

        return $tree;
    }
}
