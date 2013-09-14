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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = array(self::POSITION);

    /**
     * Closure table attributes caching array.
     *
     * @var array
     */
    protected $hidden = array(
        self::ANCESTOR => null,
        self::DESCENDANT => null,
        self::DEPTH => null
    );

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = true;

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
    }

    /**
     * Gets ancestor attribute from the model.
     *
     * @return int
     */
    protected function getAncestor()
    {
        if ($this->hidden[static::ANCESTOR] === null)
        {
            $this->hidden[static::ANCESTOR] = $this->buildClosuretableQuery()
                ->where(static::DEPTH, '=', $this->getDepth())
                ->first()
                ->{static::ANCESTOR};
        }

        return $this->hidden[static::ANCESTOR];
    }

    /**
     * Sets ancestor attribute on the model.
     *
     * @param int $value
     */
    protected function setAncestor($value)
    {
        $this->hidden[static::ANCESTOR] = (int)$value;
    }

    /**
     * Gets descendant attribute from the model.
     *
     * @return int
     */
    protected function getDescendant()
    {
        if ($this->hidden[static::DESCENDANT] === null)
        {
            $this->hidden[static::DESCENDANT] = $this->buildClosuretableQuery()
                ->where(static::DEPTH, '=', $this->getDepth())
                ->first()
                ->{static::DESCENDANT};
        }

        return $this->hidden[static::DESCENDANT];
    }

    /**
     * Sets descendant attribute on the model.
     *
     * @param int $value
     */
    protected function setDescedant($value)
    {
        $this->hidden[self::DESCENDANT] = (int)$value;
    }

    /**
     * Gets depth attribute on the model.
     *
     * @return int
     */
    protected function getDepth()
    {
        if ($this->hidden[static::DEPTH] === null)
        {
            $this->hidden[static::DEPTH] = $this->buildClosuretableQuery()->max(static::DEPTH);
        }

        return $this->hidden[static::DEPTH];
    }

    /**
     * Sets depth attribute on the model.
     *
     * @param $value
     * @return int
     */
    protected function setDepth($value)
    {
        return $this->hidden[self::DEPTH] = (int)$value;
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
            ->where($this->getQualifiedAncestorKeyName(), '<>', $this->getKey())
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
        return $this->buildChildrenQuery()->get();
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
        return (int)$this->buildChildrenQuery()->count();
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
        $this->buildChildrenQuery()->where(static::POSITION, '=', (int)$position)->first()->delete();

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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function descendants()
    {
        return $this->buildDescendantsQuery()->get();
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
     * Gets a previous model sibling.
     *
     * @return Entity
     */
    public function prevSibling($position = null)
    {
        return $this->siblings('one', 'prev', $position);
    }

    /**
     * Gets collection of previous model siblings.
     *
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
     * @return array
     */
    protected function getPrevSiblingsIds()
    {
        return $this->buildSiblingsQuery('prev')->orderBy(static::POSITION)->lists($this->getKeyName());
    }

    /**
     * Gets the next model sibling.
     *
     * @return Entity
     */
    public function nextSibling($position = null)
    {
        return $this->siblings('one', 'next', $position);
    }

    /**
     * Gets collection of the next model siblings.
     *
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
     * @return array
     */
    protected function getNextSiblingsIds()
    {
        return $this->buildSiblingsQuery('next')->orderBy(static::POSITION)->lists($this->getKeyName());
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
     * @param bool $returnObjectsArray
     * @return array
     */
    public static function tree($returnObjectsArray = true)
    {
        return array();
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
        $siblings = $this->buildSiblingsSubquery();

        if ($this->hasSiblings())
        {
            if ($this->{static::POSITION} === null)
            {
                $position = $this->siblings()->last()->{static::POSITION};
                $this->{static::POSITION} = $position+1;
                $this->save();
            }
            else
            {
                $ids = array();
                $equalPosEntity = $this->buildSiblingsQuery('both', false, $this->{static::POSITION}+1)
                    ->where($this->getQualifiedKeyName(), '<>', $this->getKey())
                    ->first();

                if ($equalPosEntity instanceof Entity)
                {
                    $ids[] = $equalPosEntity->getKey();
                }

                $ids = array_merge($ids, $this->nextSiblings()->modelKeys());
                $siblings->whereIn($this->getKeyName(), $ids)->increment(static::POSITION);
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
        $descendantValue = $this->getDescendant();

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
                SELECT tbl.{$ak}, {$descendant} as {$dk}, tbl.{$dpk}+1 as {$dpk}
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
     * @return bool|null|void
     */
    public function deleteSubtree()
    {
        $ids = $this->buildDescendantsQuery()->lists($this->getKeyName());
        $ids[] = $this->getKey();

        return $this->whereIn($this->getKeyName(), $ids)->delete();
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
}