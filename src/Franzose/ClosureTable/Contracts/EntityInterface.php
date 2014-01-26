<?php namespace Franzose\ClosureTable\Contracts;


interface EntityInterface {
    /**
     * The position column name.
     *
     * @var string
     */
    const POSITION = 'position';

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
     * @param EntityInterface $ancestor
     * @return EntityInterface
     */
    public function moveTo($position, EntityInterface $ancestor = null);
}