<?php
namespace Franzose\ClosureTable\Models;

use Franzose\ClosureTable\Contracts\ClosureTableInterface;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\DB;

/**
 * Basic ClosureTable model. Performs actions on the relationships table.
 *
 * @property mixed ancestor Alias for the ancestor attribute name
 * @property mixed descendant Alias for the descendant attribute name
 * @property int depth Alias for the depth attribute name
 *
 * @package Franzose\ClosureTable
 */
class ClosureTable extends Eloquent implements ClosureTableInterface
{
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
    protected $primaryKey = 'closure_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Inserts new node into closure table.
     *
     * @param mixed $ancestorId
     * @param mixed $descendantId
     * @return void
     */
    public function insertNode($ancestorId, $descendantId)
    {
        $rows = $this->selectRowsToInsert($ancestorId, $descendantId);

        if (!$rows->isEmpty()) {
            $this->insert($rows->toArray());
        }
    }

    private function selectRowsToInsert($ancestorId, $descendantId)
    {
        $ancestor = $this->getAncestorColumn();
        $descendant = $this->getDescendantColumn();
        $depth = $this->getDepthColumn();

        return static::select([
            DB::raw(sprintf('%s as %s', $this->qualifyColumn($ancestor), $ancestor)),
            DB::raw(sprintf('%s as %s', $descendantId, $descendant)),
            DB::raw(sprintf('%s+1 as %s', $this->qualifyColumn($depth), $depth)),
        ])
            ->where($this->qualifyColumn($descendant), $ancestorId)
            ->unionAll(DB::table(null)->select([
                DB::raw(sprintf('%s as %s', $descendantId, $ancestor)),
                DB::raw(sprintf('%s as %s', $descendantId, $descendant)),
                DB::raw(sprintf('0 as %s', $depth)),
            ]))->get();
    }

    /**
     * Make a node a descendant of another ancestor or makes it a root node.
     *
     * @param mixed $ancestorId
     * @return void
     */
    public function moveNodeTo($ancestorId = null)
    {
        $ancestor = $this->getAncestorColumn();
        $descendant = $this->getDescendantColumn();
        $depth = $this->getDepthColumn();

        // Prevent constraint collision
        if ($ancestorId !== null && $this->ancestor === $ancestorId) {
            return;
        }

        $this->unbindRelationships();

        // Since we have already unbound the node relationships,
        // given null ancestor id, we have nothing else to do,
        // because now the node is already root.
        if ($ancestorId === null) {
            return;
        }

        static::insertUsing([$ancestor, $descendant, $depth], static::select([
            $this->qualifyColumn($ancestor),
            "subtbl.{$descendant}",
            DB::raw(sprintf('%s+%s+1', $this->qualifyColumn($depth), "subtbl.{$depth}")),
        ])
            ->crossJoin(sprintf('%s as subtbl', $this->getTable()))
            ->where($this->qualifyColumn($descendant), $ancestorId)
            ->where("subtbl.{$ancestor}", $this->descendant));
    }

    /**
     * Unbinds current relationships.
     *
     * @return void
     */
    protected function unbindRelationships()
    {
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();

        static::whereIn($descendantColumn, static::select([$descendantColumn])->where($ancestorColumn, $this->descendant)->get())->whereIn($ancestorColumn, static::select([$ancestorColumn])->where($descendantColumn, $this->descendant)->where($ancestorColumn, '<>', $this->descendant)->get())->delete();
    }

    /**
     * Get table name with custom prefix for use in raw queries.
     *
     * @return string
     */
    public function getPrefixedTable()
    {
        return $this->getConnection()->getTablePrefix() . $this->getTable();
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
        $this->attributes[$this->getAncestorColumn()] = $value;
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
        return 'ancestor';
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
        $this->attributes[$this->getDescendantColumn()] = $value;
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
        return 'descendant';
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
        $this->attributes[$this->getDepthColumn()] = (int) $value;
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
        return 'depth';
    }
}
