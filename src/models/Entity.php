<?php namespace Franzose\ClosureTable;
/**
 * Database design pattern implementation for Laravel.
 *
 * @package    ClosureTable
 * @author     Jan Iwanow <iwanow.jan@gmail.com>
 * @copyright  2013 Jan Iwanow
 * @licence    MIT License <http://www.opensource.org/licenses/mit>
 */

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Support\Facades\DB;

/**
 * Generic model with Closure Table database design pattern capabilities.
 */
class Entity extends Eloquent {

    /**
     * Closure table name for this model.
     *
     * @var string
     */
    protected $closure;

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = true;

    /**
     * Node position before reordering.
     *
     * @var int|null
     */
    private $oldpos = null;

    /**
     * The position column name.
     *
     * @var string
     */
    const POSITION = 'position';

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

        if ( ! $this->closure)
        {
            $this->closure = $this->getTable().'_closure';
        }
    }

    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Gets ancestor attribute from the model.
     *
     * @return int
     */
    protected function getAncestor()
    {
        $closure = $this->buildClosuretableQuery()
            ->where(static::DEPTH, '=', $this->getDepth())
            ->first();

        return ($closure === null ? null : $closure->{static::ANCESTOR});
    }

    /**
     * Gets descendant attribute from the model.
     *
     * @return int
     */
    protected function getDescendant()
    {
        $closure = $this->buildClosuretableQuery()
            ->where(static::DEPTH, '=', $this->getDepth())
            ->first();

        return ($closure === null ? null : $closure->{static::DESCENDANT});
    }

    /**
     * Gets depth attribute on the model.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->buildClosuretableQuery()->max(static::DEPTH);
    }

    /**
     * Builds partial query for the closure table for further use.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildClosuretableQuery()
    {
        return DB::table($this->closure)->where(static::DESCENDANT, '=', $this->getKey());
    }

    /**
     * Gets direct model ancestor.
     *
     * @return Entity|null
     */
    public function parent()
    {
        return $this->select(array($this->getTable().'.*'))
            ->join($this->closure, $this->getQualifiedDescendantKeyName(), '=', $this->getKeyName())
            ->where($this->getQualifiedAncestorKeyName(), '=', $this->getAncestor())
            ->where($this->getQualifiedDepthKeyName(), '=', $this->getDepth()-1)
            ->first();
    }

    /**
     * Builds query for the model ancestors.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildAncestorsQuery()
    {
        $ak = $this->getQualifiedAncestorKeyName();
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->select($this->getTable().'.*')->join($this->closure, $ak, '=', $this->getQualifiedKeyName())
            ->where($dk, '=', $this->getKey())
            ->where($dpk, '>', 0);
    }

    /**
     * Gets all model ancestors.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ancestors()
    {
        return $this->buildAncestorsQuery()->get();
    }

    /**
     * Checks whether the model has any ancestors.
     *
     * @return bool
     */
    public function hasAncestors()
    {
        return !!$this->countAncestors();
    }

    /**
     * Counts model ancestors.
     *
     * @return int
     */
    public function countAncestors()
    {
        return (int)$this->buildAncestorsQuery()->count();
    }

    /**
     * Builds query for the direct model descendants.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildChildrenQuery()
    {
        $ak = $this->getQualifiedAncestorKeyName();
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->join($this->closure, $dk, '=', $this->getQualifiedKeyName())
            ->where($ak, '=', $this->getKey())
            ->where($dpk, '=', $this->getDepth()+1);
    }

    /**
     * Gets direct model descendants.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        $result = (isset($this->nested) ? $this->nested : $this->buildChildrenQuery()->get());

        return $result;
    }

    /**
     * Checks if the model has direct descendants.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return !!$this->countChildren();
    }

    /**
     * Counts direct model descendants.
     *
     * @return int
     */
    public function countChildren()
    {
        $result = (isset($this->nested) ? (int)$this->nested->count() : (int)$this->buildChildrenQuery()->count());

        return $result;
    }

    /**
     * Gets direct descendant at given position
     *
     * @param $position
     * @return Entity
     */
    public function childAt($position)
    {
        $result = null;

        if (isset($this->nested))
        {
            if (isset($this->nested[$position]))
            {
                $result = $this->nested[$position];
            }
        }
        else
        {
            $result = $this->buildChildrenQuery()->where(static::POSITION, '=', $position)->first();
        }

        return $result;
    }

    /**
     * Inserts a model as a direct descendant of this one.
     *
     * @param Entity $child
     * @param int|null $position
     * @param bool $returnChild
     * @return Entity
     */
    public function appendChild(Entity $child, $position = null, $returnChild = false)
    {
        $child->moveTo($this, (int)$position);

        return ($returnChild === true ? $child : $this);
    }

    /**
     * Removes a direct descendant with given position.
     *
     * @param int|null $position
     * @return Entity
     */
    public function removeChild($position = null)
    {
        if (isset($this->nested))
        {
            if (isset($this->nested[$position]))
            {
                $this->nested[$position]->delete();
            }
        }
        else
        {
            $this->buildChildrenQuery()->where(static::POSITION, '=', (int)$position)->first()->delete();
        }

        return $this;
    }

    /**
     * Builds query for the model descendants.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildDescendantsQuery()
    {
        $ak = $this->getQualifiedAncestorKeyName();
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->join($this->closure, $dk, '=', $this->getQualifiedKeyName())
            ->where($ak, '=', $this->getKey())
            ->where($dpk, '>', 0);
    }

    /**
     * Gets all model descendants.
     *
     * @param int|null $depth depth relative to the model's depth
     * @param bool $flat
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function descendants($depth = null, $flat = false)
    {
        $query = $this->buildDescendantsQuery();

        if ($depth !== null)
        {
            $query->where($this->getQualifiedDepthKeyName(), '=', $depth);
        }

        $query = $query->get();

        if ($flat === false)
        {
            return $query->toTree();
        }

        return $query;
    }

    /**
     * Checks if the model has any descendants.
     *
     * @return bool
     */
    public function hasDescendants()
    {
        return !!$this->countDescendants();
    }

    /**
     * Count the model descendants.
     *
     * @return int
     */
    public function countDescendants()
    {
        return (int)$this->buildDescendantsQuery()->count();
    }

    /**
     * Builds query for siblings of the model.
     *
     * @param string $direction
     * @param bool $queryAll
     * @param int|null $position
     * @throws \InvalidArgumentException
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSiblingsQuery($direction = 'both', $queryAll = true, $position = null)
    {
        if (!in_array($direction, array('next', 'prev', 'both')))
        {
            throw new \InvalidArgumentException('Invalid direction value.');
        }

        $query = $this->buildSiblingsSubquery();

        $operand = '';
        $position = ($position === null ? $this->{static::POSITION} : $position);
        $wherePos = null;

        switch($direction)
        {
            case 'prev':
                $operand = '<';
                $wherePos = $position-1;
                break;

            case 'next':
                $operand = '>';
                $wherePos = $position+1;
                break;

            case 'both':
                $operand = '<>';
                $wherePos = array($position-1, $position+1);
        }

        if ($queryAll === true)
        {
            $query->where(static::POSITION, $operand, $position);
        }
        else
        {
            if ($direction == 'both')
            {
                $query->whereIn(static::POSITION, $wherePos);
            }
            else
            {
                $query->where(static::POSITION, '=', $wherePos);
            }
        }

        return $query;
    }

    /**
     * Builds a part of the siblings query.
     * This part defines a sibling regardless of direction (prev or next) and position
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSiblingsSubquery()
    {
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->select(array($this->getTable().'.*'))
            ->join($this->closure, $dk, '=', $this->getQualifiedKeyName())
            ->where($dk, '<>', $this->getKey())
            ->where($dpk, '=', $this->getDepth());
    }

    /**
     * Gets the first sibling of a model.
     *
     * @return Entity
     */
    public function firstSibling()
    {
        return $this->siblingAt(0);
    }

    /**
     * Gets the last sibling of a model.
     *
     * @return Entity
     */
    public function lastSibling()
    {
        $lastpos = $this->buildSiblingsSubquery()->max(static::POSITION);
        return $this->siblingAt($lastpos);
    }

    /**
     * Gets a sibling with given position.
     *
     * @param $position
     * @return Entity
     */
    public function siblingAt($position)
    {
        return $this->siblings('one', 'next', $position-1);
    }

    /**
     * Gets a previous model sibling.
     *
     * @return Entity
     */
    public function prevSibling()
    {
        return $this->siblings('one', 'prev');
    }

    /**
     * Gets collection of previous model siblings.
     *
     * @param int|null $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevSiblings($position = null)
    {
        return $this->siblings('all', 'prev', $position);
    }

    /**
     * Checks if the model has previous siblings.
     *
     * @return bool
     */
    public function hasPrevSiblings()
    {
        return !!$this->countPrevSiblings();
    }

    /**
     * Counts previous siblings.
     *
     * @return int
     */
    public function countPrevSiblings()
    {
        return (int)$this->buildSiblingsQuery('prev')->count();
    }

    /**
     * Gets the next model sibling.
     *
     * @return Entity
     */
    public function nextSibling()
    {
        return $this->siblings('one', 'next');
    }

    /**
     * Gets collection of the next model siblings.
     *
     * @param int|null $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextSiblings($position = null)
    {
        return $this->siblings('all', 'next', $position);
    }

    /**
     * Checks if model has next siblings.
     *
     * @return bool
     */
    public function hasNextSiblings()
    {
        return !!$this->countNextSiblings();
    }

    /**
     * Counts next siblings.
     *
     * @return int
     */
    public function countNextSiblings()
    {
        return (int)$this->buildSiblingsQuery('next')->count();
    }

    /**
     * Retrieves previous or next model siblings.
     *
     * @param string $find number of the searched: 'all', 'one'
     * @param string $direction searching direction: 'prev', 'next', 'both'
     * @param int|null $position
     * @return \Illuminate\Database\Eloquent\Collection|Entity
     */
    public function siblings($find = 'all', $direction = 'both', $position = null)
    {
        switch($find)
        {
            case 'one':
                $result = $this->buildSiblingsQuery($direction, false, $position);

                if ($direction == 'both')
                {
                    $result = $result->get();
                }
                else
                {
                    $result = $result->first();
                }

                break;

            case 'all':
                $result = $this->buildSiblingsQuery($direction, true, $position)->get();
                break;
        }

        return $result;
    }

    /**
     * Checks if model has siblings.
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return !!$this->countSiblings();
    }

    /**
     * Checks if model had siblings being in at previous position.
     *
     * @return bool
     */
    protected function hadSiblings()
    {
        return !!$this->buildSiblingsQuery('both', true, $this->oldpos)->count();
    }

    /**
     * Counts model siblings.
     *
     * @return int
     */
    public function countSiblings()
    {
        return (int)$this->buildSiblingsQuery()->count();
    }

    /**
     * Retrieves all models that have no ancestors.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function roots()
    {
        $instance   = new static;
        $table      = $instance->getTable();
        $closure    = $instance->closure;
        $ancestor   = $instance->getQualifiedAncestorKeyName();
        $descendant = $instance->getQualifiedDescendantKeyName();
        $depth      = $instance->getQualifiedDepthKeyName();
        $keyName    = $instance->getQualifiedKeyName();

        $having = "(SELECT COUNT(*) FROM {$closure} WHERE {$descendant} = parentId AND {$depth} > 0) = 0";

        return static::select(array($table.'.*', $ancestor.' AS parentId'))
            ->distinct()
            ->join($closure, function($join) use($ancestor, $descendant, $keyName){
                $join->on($ancestor, '=', $keyName);
                $join->on($descendant, '=', $keyName);
            })
            ->groupBy($keyName)
            ->havingRaw($having)
            ->get();
    }

    /**
     * Checks if model is a top level one.
     *
     * @return bool
     */
    public function isRoot()
    {
        return !!$this->from($this->closure)
                      ->where(static::DESCENDANT, '=', $this->getKey())
                      ->where(static::DEPTH, '>', 0)
                      ->count() == 0;
    }

    /**
     * Moves the model and its relationships to the top level of the tree.
     *
     * @return Entity
     */
    public function makeRoot()
    {
        if ( ! $this->isRoot())
            $this->moveTo();

        return $this;
    }

    /**
     * Retrives a whole tree from the database.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function tree()
    {
        $instance = new static;
        $columns = array(
            $instance->getTable().".*",
            "closure1.".static::ANCESTOR,
            "closure1.".static::DESCENDANT,
            "closure1.".static::DEPTH
        );

        $key = $instance->getQualifiedKeyName();

        return static::select($columns)
            ->distinct()
            ->join($instance->closure.' as closure1', $key, '=', 'closure1.'.static::ANCESTOR)
            ->join($instance->closure.' as closure2', $key, '=', 'closure2.'.static::DESCENDANT)
            ->whereRaw('closure1.'.static::ANCESTOR.' = closure1.'.static::DESCENDANT)
            ->get()
            ->toTree();
    }

    /**
     * Makes the model a root or a direct descendant of the given model.
     *
     * @param Entity|null $ancestor
     * @param int|null $position
     * @return Entity
     */
    public function moveTo(Entity $ancestor = null, $position = null)
    {
        return static::moveGivenTo($this, $ancestor, $position);
    }

    /**
     * Makes given model a root or a direct descendant of another model.
     *
     * @param Entity|int $given
     * @param Entity|int|null $to
     * @param int|null $position
     * @return Entity
     */
    public static function moveGivenTo(Entity $given, Entity $to = null, $position = null)
    {
        if ($to === $given->parent() && $position == $given->{static::POSITION})
        {
            return $given;
        }

        $given->oldpos = $given->{static::POSITION};
        $given->{static::POSITION} = $position;
        $given->save();
        $given->reorderSiblings();
        $given->performMoveTo($to);

        return $given;
    }

    /**
     * Changes positions of all of the model siblings when it's moved.
     *
     * @return void
     */
    protected function reorderSiblings()
    {
        if ($this->hadSiblings())
        {
            if ($this->{static::POSITION} === null)
            {
                $position = $this->lastSibling()->{static::POSITION};
                $this->{static::POSITION} = $position+1;
                $this->save();
            }
            else
            {
                $keyName = $this->getKeyName();

                $siblingsIds = $this->buildSiblingsSubquery()
                    ->where($this->getQualifiedKeyName(), '<>', $this->getKey())
                    ->lists($keyName);

                $siblings = $this->whereIn($keyName, $siblingsIds);

                if ($this->{static::POSITION} > $this->oldpos)
                {
                    $range = range($this->oldpos, $this->{static::POSITION});
                    $siblings->whereIn(static::POSITION, $range)->decrement(static::POSITION);
                }
                else
                {
                    $range = range($this->{static::POSITION}, $this->oldpos-1);
                    $siblings->whereIn(static::POSITION, $range)->increment(static::POSITION);
                }
            }
        }
        else
        {
            $this->{static::POSITION} = 0;
            $this->save();
        }
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @return bool
     */
    protected function performInsert($query)
    {
        if (parent::performInsert($query) === true)
        {
            $id = $this->getKey();
            $parent = $this->parent();
            $parentId = ($parent instanceof Entity ? $parent->getKey() : $id);

            $this->performInsertNode($id, $parentId);
            $this->reorderSiblings();

            return true;
        }

        return false;
    }

    /**
     * Performs closure table rebuilding when the model's moved.
     *
     * @param Entity|null $ancestor
     * @return bool
     */
    protected function performMoveTo(Entity $ancestor = null)
    {
        $ak  = static::ANCESTOR;
        $dk  = static::DESCENDANT;
        $dpk = static::DEPTH;

        $ancestorValue = $this->getAncestor();
        $descendantValue = $this->getKey();

        $descendantsIds = DB::table($this->closure)
            ->where($dk, '=', $ancestorValue)
            ->where($ak, '<>', $descendantValue)
            ->lists($dk);

        // disconnect the subtree from its ancestors
        if (count($descendantsIds))
        {
            DB::table($this->closure)
                ->whereIn($dk, $descendantsIds)
                ->where($ak, '<>', $descendantValue)
                ->delete();
        }

        // null? make it root
        if ($ancestor === null)
        {
            return DB::table($this->closure)
                ->where(static::ANCESTOR, '=', $ancestorValue)
                ->where(static::DESCENDANT, '=', $descendantValue)
                ->update(array(
                    static::DEPTH => 0,
                    static::ANCESTOR => $descendantValue
            ));
        }

        $table = $this->closure;
        $ancestorId = $ancestor->getKey();

        DB::transaction(function() use($ak, $dk, $dpk, $table, $ancestorId, $descendantValue){
            $selectQuery = "
                SELECT supertbl.{$ak}, subtbl.{$dk}, supertbl.{$dpk}+subtbl.{$dpk}+1 as {$dpk}
                FROM {$table} as supertbl
                CROSS JOIN {$table} as subtbl
                WHERE supertbl.{$dk} = {$ancestorId}
                AND subtbl.{$ak} = {$descendantValue}
            ";

            $results = DB::select($selectQuery);
            array_walk($results, function(&$item){ $item = (array)$item; });

            DB::table($this->closure)->insert($results);
        });
    }

    /**
     * Performs closure table rebuilding when a new model is saved to the database.
     *
     * @param $descendant
     * @param $ancestor
     * @return mixed
     */
    protected function performInsertNode($descendant, $ancestor)
    {
        $table = $this->closure;
        $ak = static::ANCESTOR;
        $dk = static::DESCENDANT;
        $dpk = static::DEPTH;

        DB::transaction(function() use($table, $ak, $dk, $dpk, $descendant, $ancestor){
            $selectQuery = "
                SELECT tbl.{$ak} as {$ak}, {$descendant} as {$dk}, tbl.{$dpk}+1 as {$dpk}
                FROM {$table} AS tbl
                WHERE tbl.{$dk} = {$ancestor}
                UNION ALL
                SELECT {$descendant}, {$descendant}, 0
            ";

            $results = DB::select($selectQuery);
            array_walk($results, function(&$item){ $item = (array)$item; });

            DB::table($table)->insert($results);
        });
    }

    /**
     * Delete the model, all related models and relationships in the closure table.
     *
     * @param bool $forceDelete
     * @return bool|null|void
     */
    public function deleteSubtree($forceDelete = true)
    {
        $ids = $this->buildDescendantsQuery()->lists($this->getKeyName());
        $ids[] = $this->getKey();
        $query = $this->whereIn($this->getKeyName(), $ids);

        return ($forceDelete === true ? $query->forceDelete() : $query->delete());
    }

    /**
     * Get the table qualified ancestor key name.
     *
     * @return string
     */
    protected function getQualifiedAncestorKeyName()
    {
        return $this->closure.'.'.static::ANCESTOR;
    }

    /**
     * Get the table qualified descendant key name.
     *
     * @return string
     */
    protected function getQualifiedDescendantKeyName()
    {
        return $this->closure.'.'.static::DESCENDANT;
    }

    /**
     * Get the table qualified depth key name.
     *
     * @return string
     */
    protected function getQualifiedDepthKeyName()
    {
        return $this->closure.'.'.static::DEPTH;
    }

    public function newCollection(array $models = array())
    {
        return new \Franzose\ClosureTable\Collection($models);
    }
}