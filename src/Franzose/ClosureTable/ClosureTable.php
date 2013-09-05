<?php namespace Franzose\ClosureTable;
/**
 * Database design pattern realization for Laravel.
 *
 * @package    ClosureTable
 * @author     Jan Iwanow <iwanow.jan@gmail.com>
 * @copyright  2013 Jan Iwanow
 * @licence    MIT License <http://www.opensource.org/licenses/mit>
 */

use \Illuminate\Support\Facades\DB;
use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Closure table representation model.
 */
class ClosureTable extends Eloquent {

    /**
     * Static alias for $table property.
     *
     * @var string
     */
    public static $tableName;

    /**
     * Indicates if the model has update and creation timestamps.
     * In closure table, we doesn't need any timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    public $primaryKey = 'ctid';

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
     * Model constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->table = static::$tableName;
    }

    /**
     * Inserts a new node to closure table tree.
     *
     * @param $descendant
     * @param $ancestor
     * @return mixed
     */
    public static function insertLeaf($descendant, $ancestor)
    {
        $table = static::$tableName;
        $ak = static::ANCESTOR;
        $dsk = static::DESCENDANT;
        $dpk = static::DEPTH;

        $queryString = "
            INSERT INTO {$table} ({$ak}, {$dsk}, {$dpk})
              SELECT tbl.{$ak}, {$descendant}, tbl.{$dpk}+1
              FROM {$table} AS tbl
              WHERE tbl.{$dsk} = {$ancestor}
              UNION ALL
              SELECT {$descendant}, {$descendant}, 0
        ";

        return DB::unprepared($queryString);
    }

    /**
     * Moves a node and its descendants to a new location (node or root).
     *
     * @param int|null $ancestorId
     * @return bool
     */
    public function moveTo($ancestorId = null)
    {
        $ak  = static::ANCESTOR;
        $dk  = static::DESCENDANT;
        $dpk = static::DEPTH;

        $descendantsIds = DB::table($this->table)
            ->where($dk, '=', $this->{$dk})
            ->where($ak, '<>', $this->{$dk})
            ->lists($dk);

        // disconnect the subtree from its ancestors
        if (count($descendantsIds))
        {
            DB::table($this->table)
                ->whereIn($dk, $descendantsIds)
                ->where($ak, '<>', $this->{$dk})
                ->delete();
        }

        // null? make it root
        if ($ancestorId === null)
        {
            $this->{$dpk} = 0;
            $this->{$ak} = $this->{$dk};
            return $this->save();
        }

        $table = $this->table;
        $descendantId = $this->{$dk};

        // restore the subtree
        $queryString = "
            INSERT INTO {$table} ({$ak}, {$dk}, {$dpk})
            SELECT supertbl.{$ak}, subtbl.{$dk}, supertbl.{$dpk}+subtbl.{$dpk}+1
            FROM {$table} as supertbl
            CROSS JOIN {$table} as subtbl
            WHERE supertbl.{$dk} = {$ancestorId}
            AND subtbl.{$ak} = {$descendantId}
        ";

        return DB::unprepared($queryString);
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $query = $this->newQuery()->where($this->getKeyName(), $this->getKey());

        if ($this->softDelete)
        {
            $this->{static::DELETED_AT} = $time = $this->freshTimestamp();

            $query->update(array(static::DELETED_AT => $this->fromDateTime($time)));
        }
        else
        {
            $query->where(static::DESCENDANT, '=', $this->{static::DESCENDANT})->delete();
        }
    }

    /**
     * Deletes entire subtree and its ancestor.
     *
     * @return bool|null
     */
    public function deleteSubtree()
    {
        $descendantsIds = DB::table($this->table)
            ->where(static::DESCENDANT, '=', $this->{static::DESCENDANT})
            ->lists(static::DESCENDANT);

        return $this->whereIn(static::DESCENDANT, $descendantsIds)->delete();
    }

    /**
     * Get the table qualified ancestor key name.
     *
     * @return string
     */
    public static function getQualifiedAncestorKeyName()
    {
        return static::$tableName.'.'.static::ANCESTOR;
    }

    /**
     * Get the table qualified descendant key name.
     *
     * @return string
     */
    public static function getQualifiedDescendantKeyName()
    {
        return static::$tableName.'.'.static::DESCENDANT;
    }

    /**
     * Get the table qualified depth key name.
     *
     * @return string
     */
    public static function getQualifiedDepthKeyName()
    {
        return static::$tableName.'.'.static::DEPTH;
    }
}