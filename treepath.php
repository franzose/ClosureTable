<?php

/**
 * Database design pattern realization for Laravel.
 *
 * @package    ClosureTable
 * @author     Jan Iwanow <iwanow.jan@gmail.com>
 * @copyright  2013 Jan Iwanow
 * @licence    MIT License <http://www.opensource.org/licenses/mit>
 */
namespace ClosureTable;
use Laravel\Database as DB;
use Laravel\Database\Eloquent\Model as Eloquent;

/**
 * Tree paths table representation model.
 *
 * @package    ClosureTable
 * @author     Jan Iwanow <iwanow.jan@gmail.com>
 * @copyright  2013 Jan Iwanow
 * @licence    MIT License <http://www.opensource.org/licenses/mit>
 */
class TreePath extends Eloquent {

    /**
     * The primary key for the model on the database table.
     * Default value, 'id', is changed to avoid query collisions.
     *
     * @var string
     */
    public static $key = 'tpid';

    /**
     * Indicates if the model has update and creation timestamps.
     * In tree paths table we doesn't need any timestamps.
     *
     * @var bool
     */
    public static $timestamps = false;

    /**
     * Sets ancestor in the current tree path. It's possible to pass an IClosureTable object.
     *
     * @param int|IClosureTable $value
     */
    public function set_ancestor($value)
    {
        $value = ($value instanceof IClosureTable) ? $value->{static::$key} : $value;
        $this->set_attribute('ancestor', $value);
    }

    /**
     * Sets descendant in the current tree path. It's possible to pass an IClosureTable object.
     *
     * @param int|IClosureTable $value
     */
    public function set_descendant($value)
    {
        $value = ($value instanceof IClosureTable) ? $value->{static::$key} : $value;
        $this->set_attribute('descendant', $value);
    }

    /**
     * Performs model's nodes insertion.
     *
     * @param int $id descendant
     * @param int $parent_id ancestor
     * @return mixed
     */
    public static function insert_leaf($id, $parent_id)
    {
        $q_str = 'insert into %s (ancestor, descendant, level)
                  select t.ancestor, %u, t.level+1
                  from %s as t
                  where t.descendant = %u
                  union all select %u, %u, (select tt.level+1
                  from %s as tt
                  where tt.descendant = %u
                  and tt.ancestor = %u)';

        $query = sprintf($q_str,
            static::$table,
            $id,
            static::$table,
            $parent_id,
            $id, $id,
            static::$table,
            $parent_id, $parent_id);

        return DB::query($query);
    }

    /**
     * Deletes a leaf node from database.
     *
     * @return mixed
     */
    public function delete()
    {
        return $this->where('descendant', '=', $this->descendant)->delete();
    }

    /**
     * Deletes a subtree completely.
     *
     * @return mixed
     */
    public function delete_subtree()
    {
        $descendants = DB::table(static::$table)
            ->select('descendant')
            ->where('ancestor', '=', $this->ancestor)
            ->get();

        $plain = array();
        foreach ($descendants as $d)
            $plain[] = $d['descendant'];

        return $this->where_in('descendant', $plain)->delete();
    }

    /**
     * Moves a node and its descendants to a new location (node or root).
     *
     * @param int|null $ancestor
     * @return bool
     */
    public function move_to($ancestor = null)
    {
        $descendants = DB::table(static::$table)
            ->select('descendant')
            ->where('ancestor', '=', $this->descendant)
            ->get();

        $ancestors = DB::table(static::$table)
            ->select('ancestor')
            ->where('descendant', '=', $this->descendant)
            ->where('ancestor', '<>', $this->descendant)
            ->get();

        $plain_descendants = array();
        foreach ($descendants as $d)
            $plain_descendants[] = $d['descendant'];

        $plain_ancestors = array();
        foreach ($ancestors as $a)
            $plain_ancestors[] = $a['ancestor'];

        unset($descendants, $ancestors);

        //disconnect the subtree from its ancestors
        DB::table(static::$table)->where_in('descendant', $plain_descendants)
            ->where_in('ancestor', $plain_ancestors)
            ->delete();

        //null? make it root
        if ($ancestor === null)
        {
            $this->level = 0;
            $this->ancestor = $this->descendant;
            return $this->save();
        }

        $q_str = 'insert into %s (ancestor, descendant, level)
                  select supertree.ancestor, subtree.descendant, subtree.level
                  from %s as supertree
                  cross join %s as subtree
                  where supertree.descendant = %d
                  and subtree.ancestor = %d';

        $query = sprintf($q_str, static::$table, static::$table, static::$table, $ancestor, $this->descendant);
        $result = DB::query($query);

        return $result;
    }
}
