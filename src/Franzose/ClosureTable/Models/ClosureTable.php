<?php namespace Franzose\ClosureTable\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Franzose\ClosureTable\Contracts\ClosureTableInterface;

/**
 * Class ClosureTable
 * @package Franzose\ClosureTable
 */
class ClosureTable extends Eloquent implements ClosureTableInterface {

    /**
     * @var string
     */
    protected $table = 'entities_closure';

    /**
     * @var string
     */
    protected $primaryKey = 'ctid';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Check if model is a top level one (i.e. has no ancestors).
     *
     * @param null $id
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isRoot($id = null)
    {
        if ( ! is_null($id) && ! is_int($id))
        {
            throw new \InvalidArgumentException('`id` argument must be of type int.');
        }

        $id = (is_int($id) ?: $this->getKey());

        return !!$this->where($this->getDescendantColumn(), '=', $id)
            ->where($this->getDepthColumn(), '>', 0)
            ->count() == 0;
    }

    /**
     * Retrieves node attributes from its actual depth.
     *
     * @param array $attributes
     * @return array|null
     */
    public function getActualAttrs($attributes = ['*'])
    {
        if ( ! is_array($attributes))
        {
            $attributes = [$attributes];
        }

        $descendantColumn = $this->getDescendantColumn();

        $closure = static::where($descendantColumn, '=', $this->{$descendantColumn})
            ->orderBy($this->getDepthColumn(), 'desc')
            ->first($attributes);

        if (is_null($closure))
        {
            return null;
        }

        $closure = $closure->toArray();

        $result = (count($closure) == 1 ? $closure[$attributes[0]] : $closure);

        return $result;
    }

    /**
     * Inserts new node into closure table.
     *
     * @param int $ancestorId
     * @param int $descendantId
     * @return mixed
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

        \DB::transaction(function() use($t, $ak, $dk, $dpk, $ancestorId, $descendantId){
            $rawTable = \DB::getTablePrefix().$t;

            $query = "
                SELECT tbl.{$ak} as {$ak}, {$descendantId} as {$dk}, tbl.{$dpk}+1 as {$dpk}
                FROM {$rawTable} AS tbl
                WHERE tbl.{$dk} = {$ancestorId}
                UNION ALL
                SELECT {$descendantId}, {$descendantId}, 0
            ";

            $results = \DB::select($query);

            array_walk($results, function(&$item){ $item = (array)$item; });

            if (\DB::table($t)->insert($results))
            {
                $this->{$ak} = $results[0][$ak];
                $this->{$dk} = $results[0][$dk];
                $this->{$dpk} = $results[0][$dpk];
            }
        });
    }

    /**
     * Make a node a descendant of another ancestor or makes it a root node.
     *
     * @param int $ancestorId
     * @return mixed
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

        $thisAncestorId = $this->{$ak};
        $thisDescendantId = $this->{$dk};

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

        \DB::transaction(function() use($ak, $dk, $dpk, $t, $ancestorId, $thisDescendantId){
            $query = "
                SELECT supertbl.{$ak}, subtbl.{$dk}, supertbl.{$dpk}+subtbl.{$dpk}+1 as {$dpk}
                FROM {$t} as supertbl
                CROSS JOIN {$t} as subtbl
                WHERE supertbl.{$dk} = {$ancestorId}
                AND subtbl.{$ak} = {$thisDescendantId}
            ";

            $results = \DB::select($query);
            array_walk($results, function(&$item){ $item = (array)$item; });

            \DB::table($t)->insert($results);
        });
    }

    /**
     * Unbindes current relationships.
     */
    protected function unbindRelationships()
    {
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();

        $descendant = $this->{$descendantColumn};

        $ancestorsIds = \DB::table($this->table)
            ->where($descendantColumn, '=', $descendant)
            ->where($ancestorColumn, '<>', $descendant)
            ->lists($ancestorColumn);

        if (count($ancestorsIds))
        {
            \DB::table($this->table)
                ->whereIn($ancestorColumn, $ancestorsIds)
                ->where($descendantColumn, '=', $descendant)
                ->delete();
        }
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