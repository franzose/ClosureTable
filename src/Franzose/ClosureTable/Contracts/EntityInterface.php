<?php namespace Franzose\ClosureTable\Contracts;


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
     * @param int $position
     * @param EntityInterface|int $ancestor
     * @return EntityInterface
     */
    public function moveTo($position, $ancestor = null);
}