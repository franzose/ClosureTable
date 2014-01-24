<?php namespace Franzose\ClosureTable\Contracts;

/**
 * Interface EntityRepositoryInterface
 * @package Franzose\ClosureTable
 */
interface EntityRepositoryInterface {

    /**
     * @param array $columns
     * @return mixed
     */
    public function all(array $columns = ['*']);

    /**
     * @param int $id
     * @param array $columns
     * @return EntityInterface
     */
    public function find($id, array $columns = ['*']);

    /**
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByAttributes(array $attributes);

    /**
     * @param array $columns
     * @return EntityInterface
     */
    public function parent(array $columns = ['*']);

    /**
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ancestors(array $columns = ['*']);

    /**
     * @return bool
     */
    public function hasAncestors();

    /**
     * @return int
     */
    public function countAncestors();

    /**
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function descendants(array $columns = ['*']);

    /**
     * @return bool
     */
    public function hasDescendants();

    /**
     * @return int
     */
    public function countDescendants();

    /**
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function children(array $columns = ['*']);

    /**
     * @return bool
     */
    public function hasChildren();

    /**
     * @return int
     */
    public function countChildren();

    /**
     * @param int $position
     * @param array $columns
     * @return EntityInterface
     */
    public function childAt($position, array $columns = ['*']);

    /**
     * @param array $columns
     * @return EntityInterface
     */
    public function firstChild(array $columns = ['*']);

    /**
     * @param array $columns
     * @return EntityInterface
     */
    public function lastChild(array $columns = ['*']);

    /**
     * @param EntityInterface $child
     * @param int $position
     */
    public function appendChild(EntityInterface $child, $position = null);

    /**
     * @param array|\Illuminate\Database\Eloquent\Collection $children
     * @throws \InvalidArgumentException
     */
    public function appendChildren($children);

    /**
     * @param int $position
     * @param bool $forceDelete
     * @return bool
     */
    public function removeChild($position = null, $forceDelete = false);

    /**
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     * @throws \InvalidArgumentException
     */
    public function removeChildren($from, $to = null, $forceDelete = false);

    /**
     * @param array $columns
     * @return mixed
     */
    public function siblings(array $columns = ['*']);

    /**
     * @return bool
     */
    public function hasSiblings();

    /**
     * @return int
     */
    public function countSiblings();

    /**
     * @param array $columns
     * @return mixed
     */
    public function neighbors(array $columns = ['*']);

    /**
     * @param int $position
     * @param array $columns
     * @return mixed
     */
    public function siblingAt($position, array $columns = ['*']);

    /**
     * @param array $columns
     * @return mixed
     */
    public function firstSibling(array $columns = ['*']);

    /**
     * @param array $columns
     * @return mixed
     */
    public function lastSibling(array $columns = ['*']);

    /**
     * @param array $columns
     * @return mixed
     */
    public function prevSibling(array $columns = ['*']);

    /**
     * @param array $columns
     * @return mixed
     */
    public function prevSiblings(array $columns = ['*']);

    /**
     * @return bool
     */
    public function hasPrevSiblings();

    /**
     * @return int
     */
    public function countPrevSiblings();

    /**
     * @param array $columns
     * @return mixed
     */
    public function nextSibling(array $columns = ['*']);

    /**
     * @param int $offset
     * @param array $columns
     * @return mixed
     */
    public function nextSiblings($offset = null, array $columns = ['*']);

    /**
     * @return bool
     */
    public function hasNextSiblings();

    /**
     * @return int
     */
    public function countNextSiblings();

    /**
     * @param array $columns
     * @return mixed
     */
    public function roots(array $columns = ['*']);

    /**
     * @param int $position
     * @return mixed
     */
    public function makeRoot($position = null);

    /**
     * @param array $columns
     * @return mixed
     */
    public function tree(array $columns = ['*']);

    /**
     *
     * @param int $position
     * @param EntityInterface $ancestor
     * @return mixed
     */
    public function moveTo($position, EntityInterface $ancestor = null);

    /**
     * @return bool
     */
    public function save();

    /**
     * @param bool $forceDelete
     * @return bool
     */
    public function destroy($forceDelete = false);

    /**
     * @param bool $withAncestor
     * @param bool $forceDelete
     * @return mixed
     */
    public function destroySubtree($withAncestor = false, $forceDelete = false);
} 