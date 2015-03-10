<?php
namespace Franzose\ClosureTable\Traits;

use DB;

/**
 * Basic ClosureTable trait. Performs actions on the relationships table
 *
 * @package Franzose\ClosureTable\Traits *
 * @property int ancestor Alias for the ancestor attribute name
 * @property int descendant Alias for the descendant attribute name
 * @property int depth Alias for the depth attribute name
 */
trait ClosureTableTrait
{
    /**
     * Column name for the descendant column
     * @var string
     */
    protected $descendantColumn = 'descendant';

    /**
     * Column name for the ancestor ID
     *
     * @var string
     */
    protected $ancestorColumn = 'ancestor';

    /**
     * The column name for the depth.
     *
     * @var string
     */
    protected $depthColumn = 'depth';

    /**
     * Inserts new node into closure table.
     *
     * @param int $ancestorId
     * @param int $descendantId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function insertNode($ancestorId, $descendantId)
    {
        if (!is_numeric($ancestorId) || !is_numeric($descendantId)) {
            throw new \InvalidArgumentException('`ancestorId` and `descendantId` arguments must be of type int.');
        }

        $table = $this->getPrefixedTable();
        $ancestor = $this->getAncestorColumn();
        $descendant = $this->getDescendantColumn();
        $depth = $this->getDepthColumn();

        $query = "
            INSERT INTO {$table} ({$ancestor}, {$descendant}, {$depth})
            SELECT tbl.{$ancestor}, {$descendantId}, tbl.{$depth}+1
            FROM {$table} AS tbl
            WHERE tbl.{$descendant} = {$ancestorId}
            UNION ALL
            SELECT {$descendantId}, {$descendantId}, 0
        ";

        DB::statement($query);
    }

    /**
     * Make a node a descendant of another ancestor or makes it a root node.
     *
     * @param int $ancestorId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function moveNodeTo($ancestorId = null)
    {
        if (!is_null($ancestorId) && !is_numeric($ancestorId)) {
            throw new \InvalidArgumentException('`ancestor` argument must be of type int.');
        }

        $table = $this->getPrefixedTable();
        $ancestor = $this->getAncestorColumn();
        $descendant = $this->getDescendantColumn();
        $depth = $this->getDepthColumn();

        $thisAncestorId = $this->ancestor;
        $thisDescendantId = $this->descendant;

        // Prevent constraint collision
        if (!is_null($ancestorId) && $thisAncestorId === $ancestorId) {
            return;
        }

        $this->unbindRelationships();

        // Since we have already unbound the node relationships,
        // given null ancestor id, we have nothing else to do,
        // because now the node is already root.
        if (is_null($ancestorId)) {
            return;
        }

        $query = "
            INSERT INTO {$table} ({$ancestor}, {$descendant}, {$depth})
            SELECT supertbl.{$ancestor}, subtbl.{$descendant}, supertbl.{$depth}+subtbl.{$depth}+1
            FROM {$table} as supertbl
            CROSS JOIN {$table} as subtbl
            WHERE supertbl.{$descendant} = {$ancestorId}
            AND subtbl.{$ancestor} = {$thisDescendantId}
        ";

        DB::statement($query);
    }

    /**
     * Unbinds current relationships.
     *
     * @return void
     */
    protected function unbindRelationships()
    {
        $table = $this->getPrefixedTable();
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();
        $descendant = $this->descendant;

        $query = "
            DELETE FROM {$table}
            WHERE {$descendantColumn} IN (
              SELECT d FROM (
                SELECT {$descendantColumn} as d FROM {$table}
                WHERE {$ancestorColumn} = {$descendant}
              ) as dct
            )
            AND {$ancestorColumn} IN (
              SELECT a FROM (
                SELECT {$ancestorColumn} AS a FROM {$table}
                WHERE {$descendantColumn} = {$descendant}
                AND {$ancestorColumn} <> {$descendant}
              ) as ct
            )
        ";

        DB::delete($query);
    }

    /**
     * Get table name with custom prefix for use in raw queries.
     *
     * @return string
     */
    public function getPrefixedTable()
    {
        return DB::getTablePrefix() . $this->getTable();
    }

    /**
     * Get value of the "ancestor" attribute.
     *
     * @return int
     */
    public function getAncestorAttribute()
    {
        return $this->getAttributeFromArray($this->getAncestorColumn());
    }

    /**
     * Set new ancestor id.
     *
     * @param $value
     */
    public function setAncestorAttribute($value)
    {
        $this->attributes[$this->getAncestorColumn()] = intval($value);
    }

    /**
     * Get the fully qualified "ancestor" column.
     *
     * @return string
     */
    public function getQualifiedAncestorColumn()
    {
        return $this->getTable() . '.' . $this->getAncestorColumn();
    }

    /**
     * Get the short name of the "ancestor" column.
     *
     * @return string
     */
    public function getAncestorColumn()
    {
        return $this->ancestorColumn;
    }

    /**
     * Get value of the "descendant" attribute.
     *
     * @return int
     */
    public function getDescendantAttribute()
    {
        return $this->getAttributeFromArray($this->getDescendantColumn());
    }

    /**
     * Set new descendant id.
     *
     * @param $value
     */
    public function setDescendantAttribute($value)
    {
        $this->attributes[$this->getDescendantColumn()] = intval($value);
    }

    /**
     * Get the fully qualified "descendant" column.
     *
     * @return string
     */
    public function getQualifiedDescendantColumn()
    {
        return $this->getTable() . '.' . $this->getDescendantColumn();
    }

    /**
     * Get the short name of the "descendant" column.
     *
     * @return string
     */
    public function getDescendantColumn()
    {
        return $this->descendantColumn;
    }

    /**
     * Gets value of the "depth" attribute.
     *
     * @return int
     */
    public function getDepthAttribute()
    {
        return $this->getAttributeFromArray($this->getDepthColumn());
    }

    /**
     * Sets new depth.
     *
     * @param $value
     */
    public function setDepthAttribute($value)
    {
        $this->attributes[$this->getDepthColumn()] = intval($value);
    }

    /**
     * Gets the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDepthColumn()
    {
        return $this->getTable() . '.' . $this->getDepthColumn();
    }

    /**
     * Get the short name of the "depth" column.
     *
     * @return string
     */
    public function getDepthColumn()
    {
        return $this->depthColumn;
    }
}
