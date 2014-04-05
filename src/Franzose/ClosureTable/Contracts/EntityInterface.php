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
    const REAL_DEPTH = 'real_depth';

    /**
     * Relations array key that stores children collection.
     *
     * @var string
     */
    const CHILDREN = 'children';

    /**
     * "Query all models" flag.
     *
     * @var string
     */
    const QUERY_ALL = 'all';

    /**
     * "Query all previous models" flag.
     *
     * @var string
     */
    const QUERY_PREV_ALL = 'prev_all';

    /**
     * "Query one previous model" flag.
     *
     * @var string
     */
    const QUERY_PREV_ONE = 'prev_one';

    /**
     * "Query all next models" flag.
     *
     * @var string
     */
    const QUERY_NEXT_ALL = 'next_all';

    /**
     * "Query one next model" flag.
     *
     * @var string
     */
    const QUERY_NEXT_ONE = 'next_one';

    /**
     * "Query models that are neighbors to this model" flag.
     *
     * @var string
     */
    const QUERY_NEIGHBORS = 'neighbors';

    /**
     * "Query the last model" flag.
     *
     * @var string
     */
    const QUERY_LAST = 'last';

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
     * Retrieves ancestors applying given conditions.
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getAncestorsWhere($column, $operator = null, $value = null, array $columns = ['*']);

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
     * Retrieves descendants applying given conditions.
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getDescendantsWhere($column, $operator = null, $value = null, array $columns = ['*']);

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
    public function addChild(EntityInterface $child, $position = null);

    /**
     * Appends multiple children to the model.
     *
     * @param array $children
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addChildren(array $children);

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
     * Retrieves siblings within given positions range.
     *
     * @param int $from
     * @param int $to
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getSiblingsRange($from, $to = null, array $columns = ['*']);

    /**
     * Appends a sibling within the current depth.
     *
     * @param EntityInterface $sibling
     * @param int|null $position
     * @return $this
     */
    public function addSibling(EntityInterface $sibling, $position = null);

    /**
     * Appends multiple siblings within the current depth.
     *
     * @param array $siblings
     * @param int|null $from
     * @return mixed
     */
    public function addSiblings(array $siblings, $from = null);

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
    public static function createFromArray(array $tree, EntityInterface $parent);

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