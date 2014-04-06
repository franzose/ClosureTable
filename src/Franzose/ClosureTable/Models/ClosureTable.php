<?php namespace Franzose\ClosureTable\Models;

use DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Builder;
use Franzose\ClosureTable\Contracts\ClosureTableInterface;

/**
 * Basic ClosureTable model. Performs actions on the relationships table.
 *
 * @property int ancestor Alias for the ancestor attribute name
 * @property int descendant Alias for the descendant attribute name
 * @property int depth Alias for the depth attribute name
 *
 * @package Franzose\ClosureTable
 */
class ClosureTable extends Eloquent implements ClosureTableInterface {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'entities_closure';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ctid';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

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
        if ( ! is_numeric($ancestorId) || ! is_numeric($descendantId))
        {
            throw new \InvalidArgumentException('`ancestorId` and `descendantId` arguments must be of type int.');
        }

        $t   = $this->table;
        $ak  = $this->getAncestorColumn();
        $dk  = $this->getDescendantColumn();
        $dpk = $this->getDepthColumn();

        DB::transaction(function() use($t, $ak, $dk, $dpk, $ancestorId, $descendantId){
            $rawTable = DB::getTablePrefix().$t;

            $query = "
                INSERT INTO {$t} ({$ak}, ${dk}, ${dpk})
                SELECT tbl.{$ak} as {$ak}, {$descendantId} as {$dk}, tbl.{$dpk}+1 as {$dpk}
                FROM {$rawTable} AS tbl
                WHERE tbl.{$dk} = {$ancestorId}
                UNION ALL
                SELECT {$descendantId}, {$descendantId}, 0
            ";

            $results = DB::statement($query);
        });
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
        if ( ! is_null($ancestorId) && ! is_numeric($ancestorId))
        {
            throw new \InvalidArgumentException('`ancestor` argument must be of type int.');
        }

        $t   = $this->table;
        $ak  = $this->getAncestorColumn();
        $dk  = $this->getDescendantColumn();
        $dpk = $this->getDepthColumn();

        $thisAncestorId = $this->ancestor;
        $thisDescendantId = $this->descendant;

        // Prevent constraint collision
        if ( ! is_null($ancestorId) && $thisAncestorId === $ancestorId)
        {
            return;
        }

        $this->unbindRelationships();

        // Since we have already unbound the node relationships,
        // given null ancestor id, we have nothing else to do,
        // because now the node is already root.
        if (is_null($ancestorId))
        {
            return;
        }

        DB::transaction(function() use($ak, $dk, $dpk, $t, $ancestorId, $thisDescendantId){
            $query = "
                SELECT supertbl.{$ak}, subtbl.{$dk}, supertbl.{$dpk}+subtbl.{$dpk}+1 as {$dpk}
                FROM {$t} as supertbl
                CROSS JOIN {$t} as subtbl
                WHERE supertbl.{$dk} = {$ancestorId}
                AND subtbl.{$ak} = {$thisDescendantId}
            ";

            $results = DB::select($query);
            array_walk($results, function(&$item){ $item = (array)$item; });

            DB::table($t)->insert($results);
        });
    }

    /**
     * Unbinds current relationships.
     *
     * @return void
     */
    protected function unbindRelationships()
    {
        $table = $this->getTable();
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
     * Gets value of the "ancestor" attribute.
     *
     * @return int
     */
    public function getAncestorAttribute()
    {
        return $this->getAttributeFromArray($this->getAncestorColumn());
    }

    /**
     * Sets new ancestor id.
     *
     * @param $value
     */
    public function setAncestorAttribute($value)
    {
        $this->attributes[$this->getAncestorColumn()] = intval($value);
    }

    /**
     * Gets the fully qualified "ancestor" column.
     *
     * @return string
     */
    public function getQualifiedAncestorColumn()
    {
        return $this->getTable() . '.' . static::ANCESTOR;
    }

    /**
     * Get the short name of the "ancestor" column.
     *
     * @return string
     */
    public function getAncestorColumn()
    {
        return static::ANCESTOR;
    }

    /**
     * Gets value of the "descendant" attribute.
     *
     * @return int
     */
    public function getDescendantAttribute()
    {
        return $this->getAttributeFromArray($this->getDescendantColumn());
    }

    /**
     * Sets new descendant id.
     *
     * @param $value
     */
    public function setDescendantAttribute($value)
    {
        $this->attributes[$this->getDescendantColumn()] = intval($value);
    }

    /**
     * Gets the fully qualified "descendant" column.
     *
     * @return string
     */
    public function getQualifiedDescendantColumn()
    {
        return $this->getTable() . '.' . static::DESCENDANT;
    }

    /**
     * Get the short name of the "descendant" column.
     *
     * @return string
     */
    public function getDescendantColumn()
    {
        return static::DESCENDANT;
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
        return $this->getTable() . '.' . static::DEPTH;
    }

    /**
     * Get the short name of the "depth" column.
     *
     * @return string
     */
    public function getDepthColumn()
    {
        return static::DEPTH;
    }
} 