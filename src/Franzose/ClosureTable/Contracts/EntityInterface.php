<?php namespace Franzose\ClosureTable\Contracts;


interface EntityInterface {
    /**
     * The position column name.
     *
     * @var string
     */
    const POSITION = 'position';

    /**
     * @param EntityInterface $target
     * @param int $position
     * @return EntityInterface
     */
    public function moveTo(EntityInterface $target = null, $position);
}