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
 * Generic model with some special abilities provided by closure tables database design pattern.
 *
 * @package    ClosureTable
 * @author     Jan Iwanow <iwanow.jan@gmail.com>
 * @copyright  2013 Jan Iwanow
 * @licence    MIT License <http://www.opensource.org/licenses/mit>
 */
abstract class ClosureTable extends Eloquent implements IClosureTable {

    /**
     * Tree paths table name.
     * Allows to avoid creating a new TreePath model for each new ClosureTable model.
     * You must change it in every new ClosureTable model you create.
     *
     * For example:
     * <code>
     * public static $treepath = 'categories_treepath';
     * </code>
     *
     * @var string
     */
    public static $treepath;

    /**
     * Foreign key name of the ClosureTable model.
     *
     * We need to define it explicitly in the model because it is used widely in relationships
     * and there would be no way to get or set its value if its name would be different for each ClosureTable model.
     *
     * So, everywhere we use $this->{static::$parent_key} statement to get or set foreign key value.
     *
     * @var string
     * @see ClosureTable::parent()
     * @see ClosureTable::ancestors()
     * @see ClosureTable::descendants()
     * @see ClosureTable::move_to()
     * @see ClosureTable::siblings()
     */
    public static $parent_key = 'parent_id';

    /**
     * Model constructor.
     *
     * You don't need to create a separate TreePath model yourself
     * every time you want to use a closure tables feature.
     * You must provide a closure table name stored in static $treepath variable,
     * so TreePath model, used by ClosureTable bundle, will automatically set it for further use.
     *
     * @param array $attributes
     * @param bool $exists
     */
    public function __construct($attributes = array(), $exists = false)
    {
        parent::__construct($attributes, $exists);
        TreePath::$table = static::$treepath;
    }

    /**
     * Gets level of this model in the tree.
     *
     * @return mixed
     */
    public function get_level()
    {
        return $this->treepath->level;
    }

    /**
     * Sets tree level value for this model
     *
     * @param int $value
     */
    public function set_level($value)
    {
        $this->treepath->level = (int)$value;
    }

    /**
     * Relation to the immediate model's parent.
     *
     * @return \Laravel\Database\Eloquent\Relationship
     */
    public function parent()
    {
        return $this->belongs_to(get_class($this), static::$parent_key);
    }

    /**
     * Checks if model has a parent.
     *
     * @return bool
     */
    public function has_parent()
    {
        return !!$this->parent()->count();
    }

    /**
     * Model's tree path relation.
     *
     * @return \Laravel\Database\Eloquent\Relationship
     */
    public function treepath()
    {
        return $this->has_one('ClosureTable\TreePath', 'descendant');
    }

    /**
     * Relation to model's ancestors.
     *
     * @return mixed
     */
    public function ancestors()
    {
        $ancestor = static::$treepath.'.ancestor';
        $descendant = static::$treepath.'.descendant';
        $key = static::$table.'.'.static::$key;
        
        return $this->has_many(get_class($this), static::$parent_key)
            ->join(static::$treepath, function($join) use($ancestor, $descendant, $key) {
                $join->on($ancestor, '=', $key);
                $join->on($descendant, '<>', $key);
        })->select(array(static::$table.'.*'));
    }

    /**
     * Relation to model's descendants.
     *
     * @return mixed
     */
    public function descendants()
    {
        $ancestor = static::$treepath.'.ancestor';
        $descendant = static::$treepath.'.descendant';
        $key = static::$table.'.'.static::$key;
        
        return $this->has_many(get_class($this), static::$parent_key)
            ->join(static::$treepath, function($join) use($ancestor, $descendant, $key) {
                $join->on($descendant, '=', $key);
                $join->on($ancestor, '<>', $key);
        })->select(array(static::$table.'.*'));
    }

    /**
     * Checks whether a model have descendants.
     *
     * @return bool
     */
    public function has_descendants()
    {
        return !!$this->descendants()->count();
    }

    /**
     * @param string $order_by
     * @return array
     */
    public static function fulltree($order_by = '')
    {
        $sql = 'select distinct t0.*, t1.ancestor, t1.descendant, t1.level
                from '.static::$table.' as t0
                inner join '.static::$treepath.' as t1 on t0.id = t1.ancestor
                inner join '.static::$treepath.' as t2 on t0.id = t2.descendant
                where t1.ancestor = t1.descendant';

        if ($order_by)
            $sql .= ' order by t0.'.$order_by;

        return static::_make_multi_array(DB::query($sql));
    }

    /**
     * Makes multidimensional array from flat database results
     *
     * @param array $raw
     * @param int $index
     * @return array
     */
    protected static function _make_multi_array(array $raw, $index = null)
    {
        $result = array();

        foreach ($raw as $i => $e)
        {
            //here we convert stdClass object
            //to multidimensional array
            if (is_object($e))
            {
                $e = get_object_vars($e);
            }

            $pk = $e[static::$key];
            $fk = $e[static::$parent_key];
            
            if ($fk == $index)
            {
                unset($raw[$i]);
                $result[] = array_merge($e, array('children' => static::_make_multi_array($raw, $pk)));
            }
        }

        return $result;
    }

    public static function breadcrumbs()
    {
        //return static::select()
    }

    /**
     * Retrieves all root nodes.
     *
     * @return array
     */
    public static function roots()
    {
        $tkey = static::$table.'.'.static::$key;
        $a = static::$treepath.'.ancestor';
        $d = static::$treepath.'.descendant';
        $l = static::$treepath.'.level';

        return static::join(static::$treepath, function($join) use($tkey, $a, $d, $l){
            $join->on($tkey, '=', $a);
            $join->on($tkey, '=', $d);
        })->where($l, '=', 0)->get();
    }

    /**
     * Checks whether a node is root.
     *
     * @return bool
     */
    public function is_root()
    {
        return $this->level == 0 || !!$this->ancestors()->count();
    }

    /**
     * Makes a node the root.
     *
     * @return ClosureTable
     */
    public function make_root()
    {
        if ( ! $this->is_root())
            $this->move_to();

        return $this;
    }

    /**
     * Moves a node as a child to provided node or, if none parent node provided, makes it root.
     *
     * @param IClosureTable $ancestor
     * @param int $position
     * @return ClosureTable
     */
    public function move_to(IClosureTable $ancestor = null, $position = 0)
    {
        if ($ancestor === null)
        {
            $this->{static::$parent_key} = null;
            $this->save();
            return $this->treepath->move_to(null);
        }

        $a = $ancestor->treepath->ancestor;
        $k = $ancestor->get_key();

        $this->level = $ancestor->level+1;
        $this->treepath->save();
        $this->treepath->move_to($a);
        $this->{static::$parent_key} = $k;
        $this->position = $position;
        $this->save();

        return $this;
    }

    /**
     * Wrapper for a descendant insertion.
     *
     * @param IClosureTable $model
     * @return ClosureTable
     */
    public function append_child(IClosureTable $model)
    {
        $this->descendants()->insert($model);
        return $this;
    }

    /**
     * Removes a descendant with given position.
     *
     * @param int|null $position
     * @return mixed
     */
    public function remove_child($position = null)
    {
        if ($position === null)
            $position = 0;

        return $this->descendants()->where('position', '=', $position)->delete();
    }

    /**
     * Retrieves this model's siblings.
     *
     * @param string $find
     * @param string $direction
     * @return mixed
     */
    public function siblings($find = 'all', $direction = 'next')
    {
        $pos = null;

        switch($direction)
        {
            case 'prev':
                $pos = $this->position-1;
                break;

            case 'next':
                $pos = $this->position+1;
                break;
        }

        $result = $this->select(array(static::$table.'.*'))
            ->join(static::$treepath, static::$treepath.'.descendant', '=', static::$table.'.'.static::$key);

        if ($this->{static::$parent_key} !== null)
        {
            $result->where(static::$treepath.'.ancestor', '=', $this->treepath->ancestor);
        }

        $result->where(static::$treepath.'.level', '=', $this->level)
            ->where(static::$table.'.position', '=', $pos);

        switch($find)
        {
            case 'one':
                $result = $result->first();
                break;

            case 'all':
                $result = $result->get();
                break;
        }

        return $result;
    }

    /**
     * Gets an immediate previous sibling
     *
     * @return mixed
     */
    public function get_prev_sibling()
    {
        return $this->siblings('one', 'prev');
    }

    /**
     * Gets all model's previous siblings
     *
     * @return mixed
     */
    public function get_prev_siblings()
    {
        return $this->siblings('all', 'prev');
    }

    /**
     * Gets immediate next sibling
     *
     * @return mixed
     */
    public function get_next_sibling()
    {
        return $this->siblings('one');
    }

    /**
     * Gets all next siblings
     *
     * @return mixed
     */
    public function get_next_siblings()
    {
        return $this->siblings();
    }

    /**
     * Saves the model instance to the database. Also saves tree paths information.
     *
     * @return bool
     */
    public function save()
    {
        if ( ! $this->dirty()) return true;

        if (static::$timestamps)
            $this->timestamp();

        $this->fire_event('saving');
        $result = $this->exists ? $this->_update() : $this->_insert();
        $this->original = $this->attributes;

        if ($result)
            $this->fire_event('saved');

        return $result;
    }

    /**
     * Performs model and its nodes insertion.
     *
     * @return bool
     */
    protected function _insert()
    {
        $id = $this->query()->insert_get_id($this->attributes, $this->key());
        $this->set_key($id);
        $this->exists = $result = is_numeric($this->get_key());

        if ($result)
        {
            $parent_id = $this->parent ? $this->parent->get_key() : null;//$id;
            TreePath::insert_leaf($id, $parent_id);
            $this->fire_event('created');
        }

        return $result;
    }

    /**
     * Performs model and its nodes update.
     *
     * @return bool
     */
    protected function _update()
    {
        $query  = $this->query()->where(static::$key, '=', $this->get_key());
        $result = $query->update($this->get_dirty()) === 1;

        if ($result)
        {
            $this->move_to($this->parent()->first());
            $this->fire_event('updated');
        }

        return $result;
    }

    /**
     * Deletes the model from the database.
     * Also corrects tree paths of its descendants if those exist.
     *
     * @return int
     */
    public function delete()
    {
        $grand_parent = $this->parent()->first();
        if (!$grand_parent) $grand_parent = null;

        foreach ($this->descendants as $dsc)
        {
            $dsc->move_to($grand_parent);
        }

        $this->treepath->delete();
        return parent::delete();
    }

    /**
     * Delete the model, all its relationships and treepaths from the database.
     *
     * @return bool|int
     */
    public function delete_with_subtree()
    {
        $this->_delete_descendants();
        $this->treepath->delete_subtree();
        return $this->delete();
    }

    /**
     * Recursively deletes all model descendants.
     *
     * @param IClosureTable $ancestor
     */
    protected function _delete_descendants(IClosureTable $ancestor = null)
    {
        $ancestor = $ancestor === null ? $this : $ancestor;
        foreach ($ancestor->descendants as $descendant)
        {
            $descendant->delete();
            $descendant->_delete_descendants($descendant);
        }
    }
}
