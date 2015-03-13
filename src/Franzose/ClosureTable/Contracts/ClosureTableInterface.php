<?php
namespace Franzose\ClosureTable\Contracts;

/**
 * Basic ClosureTable model interface.
 */
interface ClosureTableInterface
{
    /**
     * Get the short name of the "ancestor" column.
     *
     * @return string
     */
    public function getAncestorColumn();

    /**
     * Get the short name of the "descendant" column.
     *
     * @return string
     */
    public function getDescendantColumn();

    /**
     * Get the short name of the "depth" column.
     *
     * @return string
     */
    public function getDepthColumn();

    /**
     * Inserts new node into closure table.
     *
     * @param int $ancestorId
     * @param int $descendantId
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function insertNode($ancestorId, $descendantId);

    /**
     * Make a node a descendant of another ancestor or makes it a root node.
     *
     * @param int $ancestorId
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function moveNodeTo($ancestorId = null);
}
