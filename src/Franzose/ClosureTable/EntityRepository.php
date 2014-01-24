<?php namespace Franzose\ClosureTable;

use Franzose\ClosureTable\Contracts\EntityInterface;
use Franzose\ClosureTable\Contracts\EntityRepositoryInterface;

/**
 * Class EntityRepository
 * @package Franzose\ClosureTable
 */
class EntityRepository implements EntityRepositoryInterface {

    /**
     * @var EntityInterface
     */
    protected $entity;

    /**
     * @param EntityInterface $entity
     */
    public function __construct(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $columns = ['*'])
    {
        return $this->entity->all($columns);
    }

    /**
     * @param $id
     * @param array $columns
     * @return Entity
     */
    public function find($id, array $columns = ['*'])
    {
        return $this->entity->find($id, $columns);
    }

    /**
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByAttributes(array $attributes)
    {
        $query = $this->entity->newQuery();

        foreach($attributes as $key => $val)
        {
            $query->where($key, $val);
        }

        return $query->get();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function parent(array $columns = ['*'])
    {
        return $this->entity->parent()->first();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function ancestors(array $columns = ['*'])
    {
        return $this->entity->ancestors($columns)->get();
    }

    /**
     * @return int
     */
    public function countAncestors()
    {
        return $this->entity->ancestors()->count();
    }

    /**
     * @return bool
     */
    public function hasAncestors()
    {
        return !!$this->countAncestors();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function descendants(array $columns = ['*'])
    {
        return $this->entity->descendants($columns)->get();
    }

    /**
     * @return int
     */
    public function countDescendants()
    {
        return $this->entity->descendants()->count();
    }

    /**
     * @return bool
     */
    public function hasDescendants()
    {
        return !!$this->countDescendants();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function children(array $columns = ['*'])
    {
        return $this->entity->children($columns)->get();
    }

    /**
     * @return int
     */
    public function countChildren()
    {
        return $this->entity->children()->count();
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !!$this->countChildren();
    }

    /**
     * @param int $index
     * @param array $columns
     * @return mixed
     */
    public function childAt($index, array $columns = ['*'])
    {
        return $this->entity->children($columns)->where(EntityInterface::POSITION, '=', $index)->first();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function firstChild(array $columns = ['*'])
    {
        return $this->childAt(0, $columns);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function lastChild(array $columns = ['*'])
    {
        return $this->entity->children($columns)->orderBy(EntityInterface::POSITION, 'desc')->first();
    }

    /**
     * @param EntityInterface $child
     * @param int $position
     */
    public function appendChild(EntityInterface $child, $position = null)
    {
        $child->moveTo($this->entity, $position);
    }

    /**
     * @param array|\Illuminate\Database\Eloquent\Collection $children
     * @throws \InvalidArgumentException
     */
    public function appendChildren($children)
    {
        if ( ! is_array($children) && ! $children instanceof \Illuminate\Database\Eloquent\Collection)
        {
            throw new \InvalidArgumentException('Children argument must be of type array or \Illuminate\Database\Eloquent\Collection.');
        }

        \DB::transaction(function() use($children)
        {
            $lastChildPosition = $this->lastChild([EntityInterface::POSITION]);

            foreach($children as $child)
            {
                if ( ! $child instanceof EntityInterface)
                {
                    throw new \InvalidArgumentException('Array items must be of type EntityInterface.');
                }

                $this->appendChild($child, $lastChildPosition);
                $lastChildPosition++;
            }
        });
    }

    /**
     * @param int $position
     * @param bool $forceDelete
     * @return bool
     */
    public function removeChild($position = null, $forceDelete = false)
    {
        $child = $this->childAt($position);

        if ( ! is_null($child))
        {
            if ($forceDelete === true)
            {
                $child->forceDelete();

                return true;
            }
            else
            {
                return $child->delete();
            }
        }

        return false;
    }

    /**
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     * @throws \InvalidArgumentException
     */
    public function removeChildren($from, $to = null, $forceDelete = false)
    {
        if ( ! is_int($from) || ( ! is_null($to) && ! is_int($to)))
        {
            throw new \InvalidArgumentException('`from` and `to` are the position boundaries. They must be of type int.');
        }

        if (is_null($to))
        {
            $to = $this->lastChild([EntityInterface::POSITION]);
        }

        foreach(range($from, $to) as $position)
        {
            $this->removeChild($position, $forceDelete);
        }
    }

    public function siblings(array $columns = ['*'])
    {
        return $this->entity->siblings($columns)->get();
    }

    /**
     * @return int
     */
    public function countSiblings()
    {
        return $this->entity->siblings()->count();
    }

    /**
     * @return bool
     */
    public function hasSiblings()
    {
        return !!$this->countSiblings();
    }

    public function neighbors(array $columns = ['*'])
    {
        return $this->entity->neighbors($columns)->get();
    }

    /**
     * @param int $position
     * @param array $columns
     * @return Entity
     */
    public function siblingAt($position, array $columns = ['*'])
    {
        return $this->entity->siblings($columns)->where(EntityInterface::POSITION, '=', $position)->first();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function firstSibling(array $columns = ['*'])
    {
        return $this->siblingAt(0, $columns);
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function lastSibling(array $columns = ['*'])
    {
        return $this->entity->siblings($columns)->orderBy(EntityInterface::POSITION, 'desc')->first();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function prevSibling(array $columns = ['*'])
    {
        return $this->entity->prevSibling($columns)->first();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function prevSiblings(array $columns = ['*'])
    {
        return $this->entity->prevSiblings($columns)->get();
    }

    /**
     * @return int
     */
    public function countPrevSiblings()
    {
        return $this->entity->prevSiblings()->count();
    }

    /**
     * @return bool
     */
    public function hasPrevSiblings()
    {
        return !!$this->countPrevSiblings();
    }

    /**
     * @param array $columns
     * @return Entity
     */
    public function nextSibling(array $columns = ['*'])
    {
        return $this->entity->nextSibling($columns)->first();
    }

    /**
     * @param int $offset
     * @param array $columns
     * @return mixed
     */
    public function nextSiblings($offset = null, array $columns = ['*'])
    {
        return $this->entity->nextSiblings($columns)->get();
    }

    /**
     * @return int
     */
    public function countNextSiblings()
    {
        return $this->entity->nextSiblings()->count();
    }

    /**
     * @return bool
     */
    public function hasNextSiblings()
    {
        return !!$this->countNextSiblings();
    }

    /**
     * @param array $options
     * @param array $columns
     * @return mixed
     */
    public function roots(array $options = [], array $columns = ['*'])
    {
        return $this->entity->roots()->get();
    }

    /**
     * @param int $position
     * @return Entity
     */
    public function makeRoot($position = null)
    {
        return $this->moveTo(null, $position);
    }

    /**
     * @param array $options
     * @param array $columns
     * @return mixed
     */
    public function tree(array $options = [], array $columns = ['*'])
    {
        return $this->entity->tree()->get()->toTree();
    }

    /**
     * @param int $position
     * @param EntityInterface $ancestor
     * @return Entity
     */
    public function moveTo($position, EntityInterface $ancestor = null)
    {
        return $this->entity->moveTo($position, $ancestor);
    }

    /**
     * @return bool
     */
    public function save()
    {
        return $this->entity->save();
    }

    /**
     * @param bool $forceDelete
     * @return bool
     */
    public function destroy($forceDelete = false)
    {
        $query = $this->entity->where($this->entity->getKeyName(), '=', $this->entity->getKey());

        return ($forceDelete === true ? $query->forceDelete() : $query->delete());
    }

    /**
     * @param bool $withAncestor
     * @param bool $forceDelete
     * @return mixed
     */
    public function destroySubtree($withAncestor = false, $forceDelete = false)
    {
        $keyName = $this->entity->getKeyName();
        $keys    = $this->entity->descendants($keyName)->get();

        if ($withAncestor === true)
        {
            $keys[]  = $this->entity->getKey();
        }

        $query = $this->entity->whereIn($keyName, $keys);

        return ($forceDelete === true ? $query->forceDelete() : $query->delete());
    }
} 