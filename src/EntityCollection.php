<?php
declare(strict_types=1);

namespace Franzose\ClosureTable;

use Illuminate\Database\Eloquent\Collection;

/**
 * Extended Collection class. Provides some useful methods.
 *
 * @method Entity|null get($key, $default = null)
 */
class EntityCollection extends Collection
{
    /**
     * Returns a child node at the given position.
     */
    public function getChildAt(int $position): ?Entity
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position === $position;
        })->first();
    }

    /**
     * Returns the first child node.
     */
    public function getFirstChild(): ?Entity
    {
        return $this->getChildAt(0);
    }

    /**
     * Returns the last child node.
     */
    public function getLastChild(): ?Entity
    {
        return $this->sortByDesc(static function (Entity $entity) {
            return $entity->position;
        })->first();
    }

    /**
     * Filters the collection by the given positions.
     */
    public function getRange(int $from, ?int $to = null): self
    {
        return $this->filter(static function (Entity $entity) use ($from, $to) {
            if ($to === null) {
                return $entity->position >= $from;
            }

            return $entity->position >= $from && $entity->position <= $to;
        });
    }

    /**
     * Filters collection to return nodes on the "left"
     * and on the "right" from the node with the given position.
     */
    public function getNeighbors(int $position): self
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position === $position - 1 ||
                   $entity->position === $position + 1;
        });
    }

    /**
     * Filters collection to return previous siblings of a node with the given position.
     */
    public function getPrevSiblings(int $position): self
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position < $position;
        });
    }

    /**
     * Filters collection to return next siblings of a node with the given position.
     */
    public function getNextSiblings(int $position): self
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position > $position;
        });
    }

    /**
     * Retrieves children relation.
     */
    public function getChildrenOf(int $position): self
    {
        if (!$this->hasChildren($position)) {
            return new static();
        }

        return $this->getChildAt($position)->children;
    }

    /**
     * Indicates whether an item has children.
     */
    public function hasChildren(int $position): bool
    {
        $item = $this->getChildAt($position);

        return $item !== null && $item->children->count() > 0;
    }

    /**
     * Makes tree-like collection.
     */
    public function toTree(): self
    {
        $items = $this->items;

        return new static($this->makeTree($items));
    }

    /**
     * Performs actual tree building.
     *
     * @param Entity[] $items
     */
    protected function makeTree(array $items): array
    {
        /** @var Entity[] $result */
        $result = [];
        $tops = [];

        foreach ($items as $item) {
            $result[$item->getKey()] = $item;
        }

        foreach ($items as $item) {
            $parentId = $item->parent_id;

            if (array_key_exists($parentId, $result)) {
                $parent = $result[$parentId];
                $children = $parent->relationLoaded(Entity::CHILDREN_RELATION_NAME)
                    ? $parent->getRelation(Entity::CHILDREN_RELATION_NAME)
                    : new EntityCollection();

                $children->add($item);
                $parent->setRelation(Entity::CHILDREN_RELATION_NAME, $children);
            } else {
                $tops[] = $item;
            }
        }

        return $tops;
    }
}
