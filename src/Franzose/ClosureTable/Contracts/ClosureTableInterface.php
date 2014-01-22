<?php namespace Franzose\ClosureTable\Contracts;

/**
 * Interface ClosureTableInterface
 * @package Franzose\ClosureTable
 */
interface ClosureTableInterface {
    /**
     * The ancestor column name.
     *
     * @var string
     */
    const ANCESTOR = 'ancestor';

    /**
     * The descendant column name.
     *
     * @var string
     */
    const DESCENDANT = 'descendant';

    /**
     * The depth column name.
     *
     * @var string
     */
    const DEPTH = 'depth';

    /**
     * Check if model is a top level one (i.e. has no ancestors).
     *
     * @param int $id
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isRoot($id = null);

    /**
     * Inserts new node into closure table.
     *
     * @param int $ancestorId
     * @param int $descendantId
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function insertNode($ancestorId, $descendantId);

    /**
     * Make a node a descendant of another ancestor or makes it a root node.
     *
     * @param int $ancestorId
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function moveNodeTo($ancestorId = null);
} 