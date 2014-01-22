<?php namespace Franzose\ClosureTable\Contracts;


interface EntityInterface {
    /**
     * The position column name.
     *
     * @var string
     */
    const POSITION = 'position';

    /**
     * @param EntityInterface $ancestor
     * @param int $position
     * @return EntityInterface
     */
    public function moveTo(EntityInterface $ancestor = null, $position);
}