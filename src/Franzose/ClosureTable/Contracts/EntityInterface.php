<?php namespace Franzose\ClosureTable\Contracts;

/**
 * Basic Entity model interface.
 *
 * @package Franzose\ClosureTable\Contracts
 */
interface EntityInterface {

    /**
     * The parent id column name.
     *
     * @var string
     */
    const PARENT_ID = 'parent_id';

    /**
     * The position column name.
     *
     * @var string
     */
    const POSITION = 'position';

    /**
     * The "real depth" column name.
     *
     * @var string
     */
    const REAL_DEPTH = 'depth';

    /**
     * Relations array key that stores children collection.
     *
     * @var string
     */
    const CHILDREN = 'children';

    /**
     * Indicates whether the model has children.
     *
     * @return bool
     */
    public function isParent();

    /**
     * Indicates whether the model has no ancestors.
     *
     * @return bool
     */
    public function isRoot();

    /**
     * Retrieves direct ancestor of a model.
     *
     * @param array $columns
     * @return EntityInterface
     */
    public function getParent(array $columns = ['*']);

    /**
     * Retrieves all ancestors of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getAncestors(array $columns = ['*']);

    /**
     * Returns a number of model's ancestors.
     *
     * @return int
     */
    public function countAncestors();

    /**
     * Indicates whether a model has ancestors.
     *
     * @return bool
     */
    public function hasAncestors();

    /**
     * Retrieves all descendants of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getDescendants(array $columns = ['*']);

    /**
     * Retrieves all descendants of a model as a tree-like collection.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getDescendantsTree(array $columns = ['*']);

    /**
     * Returns a number of model's descendants.
     *
     * @return int
     */
    public function countDescendants();

    /**
     * Indicates whether a model has descendants.
     *
     * @return bool
     */
    public function hasDescendants();

    /**
     * Retrieves all children of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getChildren(array $columns = ['*']);

    /**
     * Returns a number of model's children.
     *
     * @return int
     */
    public function countChildren();

    /**
     *  Indicates whether a model has children.
     *
     * @return bool
     */
    public function hasChildren();

    /**
     * Retrieves a child with given position.
     *
     * @param $position
     * @param array $columns
     * @return EntityInterface
     */
    public function getChildAt($position, array $columns = ['*']);

    /**
     * Retrieves the first child.
     *
     * @param array $columns
     * @return EntityInterface
     */
    public function getFirstChild(array $columns = ['*']);

    /**
     * Retrieves the last child.
     *
     * @param array $columns
     * @return EntityInterface
     */
    public function getLastChild(array $columns = ['*']);

    /**
     * Appends a child to the model.
     *
     * @param EntityInterface $child
     * @param int $position
     * @return $this
     */
    public function appendChild(EntityInterface $child, $position = null);

    /**
     * Appends a collection of children to the model.
     *
     * @param \Franzose\ClosureTable\Extensions\Collection|\Illuminate\Database\Eloquent\Collection $children
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function appendChildren($children);

    /**
     * Removes a model's child with given position.
     *
     * @param int $position
     * @param bool $forceDelete
     * @return $this
     */
    public function removeChild($position = null, $forceDelete = false);

    /**
     * Removes model's children within a range of positions.
     *
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function removeChildren($from, $to = null, $forceDelete = false);

    /**
     * Retrives all siblings of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getSiblings(array $columns = ['*']);

    /**
     * Returns number of model's siblings.
     *
     * @return int
     */
    public function countSiblings();

    /**
     * Indicates whether a model has siblings.
     *
     * @return bool
     */
    public function hasSiblings();

    /**
     * Retrieves neighbors (immediate previous and immmediate next models) of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getNeighbors(array $columns = ['*']);

    /**
     * Retrieves a model's sibling with given position.
     *
     * @param int $position
     * @param array $columns
     * @return EntityInterface
     */
    public function getSiblingAt($position, array $columns = ['*']);

    /**
     * Retrieves the first model's sibling.
     *
     * @param array $columns
     * @return EntityInterface
     */
    public function getFirstSibling(array $columns = ['*']);

    /**
     * Retrieves the last model's sibling.
     *
     * @param array $columns
     * @return EntityInterface
     */
    public function getLastSibling(array $columns = ['*']);

    /**
     * Retrieves immediate previous sibling of a model.
     *
     * @param array $columns
     * @return EntityInterface
     */
    public function getPrevSibling(array $columns = ['*']);

    /**
     * Retrieves all previous siblings of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getPrevSiblings(array $columns = ['*']);

    /**
     * Returns number of previous siblings of a model.
     *
     * @return int
     */
    public function countPrevSiblings();

    /**
     * Indicates whether a model has previous siblings.
     *
     * @return bool
     */
    public function hasPrevSiblings();

    /**
     * Retrieves immediate next sibling of a model.
     *
     * @param array $columns
     * @return EntityInterface
     */
    public function getNextSibling(array $columns = ['*']);

    /**
     * Retrieves all next siblings of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getNextSiblings(array $columns = ['*']);

    /**
     * Returns number of next siblings of a model.
     *
     * @return int
     */
    public function countNextSiblings();

    /**
     * Indicates whether a model has next siblings.
     *
     * @return bool
     */
    public function hasNextSiblings();

    /**
     * Retrieves root (with no ancestors) models.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public static function getRoots(array $columns = ['*']);

    /**
     * Makes model a root with given position.
     *
     * @param int $position
     * @return EntityInterface
     */
    public function makeRoot($position);

    /**
     * Retrieves entire tree.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public static function getTree(array $columns = ['*']);

    /**
     * Saves models from the given attributes array.
     *
     * @param array $tree
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public static function createFromArray(array $tree);

    /**
     * @param int $position
     * @param EntityInterface|int $ancestor
     * @return EntityInterface
     */
    public function moveTo($position, $ancestor = null);

    /**
     * Deletes a subtree from database.
     *
     * @param bool $withSelf
     * @param bool $forceDelete
     * @return mixed
     */
    public function deleteSubtree($withSelf = false, $forceDelete = false);
}