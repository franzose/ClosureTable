<?php namespace Franzose\ClosureTable;
/**
 * Database design pattern realization for Laravel.
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
     * @param int $value
     */
    protected function setAncestor($value)
    {
        $this->hidden[static::ANCESTOR] = (int)$value;
    }

    /**
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
     * @param int $value
     */
    protected function setDescedant($value)
    {
        $this->hidden[self::DESCENDANT] = (int)$value;
    }

    /**
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
     * @param $value
     * @return int
     */
    protected function setDepth($value)
    {
        return $this->hidden[self::DEPTH] = (int)$value;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildClosuretableQuery()
    {
        return DB::table($this->closure)->where(static::DESCENDANT, '=', $this->getKey());
    }

    /**
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
     * Builds query for the model parents.
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
     * Gets all model parents.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ancestors()
    {
        return $this->buildAncestorsQuery()->get();
    }

    /**
     * Checks whether the model has any parents.
     *
     * @return bool
     */
    public function hasAncestors()
    {
        return !!$this->countAncestors();
    }

    /**
     * Counts model parents.
     *
     * @return int
     */
    public function countAncestors()
    {
        return (int)$this->buildAncestorsQuery()->count();
    }

    /**
     * Builds query for the direct model children.
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
     * Gets direct model children.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        return $this->buildChildrenQuery()->get();
    }

    /**
     * Checks whether the model has direct children.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return !!$this->countChildren();
    }

    /**
     * Counts direct model children.
     *
     * @return int
     */
    public function countChildren()
    {
        return (int)$this->buildChildrenQuery()->count();
    }

    /**
     *
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
     * Removes a child with given position.
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
     * Builds query for the model children.
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
     * Grab all model children.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function descendants()
    {
        return $this->buildDescendantsQuery()->get();
    }

    /**
     * Checks whether the model has any children.
     *
     * @return bool
     */
    public function hasDescendants()
    {
        return !!$this->countDescendants();
    }

    /**
     * Count the model children.
     *
     * @return int
     */
    public function countDescendants()
    {
        return (int)$this->buildDescendantsQuery()->count();
    }

    /**
     * @param string $direction 'prev' for previous siblings, 'next' for next ones
     * @param bool $queryAll
     * @throws \InvalidArgumentException
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSiblingsQuery($direction = 'both', $queryAll = true)
    {
        if (!in_array($direction, array('next', 'prev', 'both')))
        {
            throw new \InvalidArgumentException('Invalid direction value.');
        }

        $query = $this->buildSiblingsSubquery();

        $operand = '';
        $position = null;

        switch($direction)
        {
            case 'prev':
                $operand = '<';
                $position = $this->{static::POSITION}-1;
                break;

            case 'next':
                $operand = '>';
                $position = $this->{static::POSITION}+1;
                break;

            case 'both':
                $operand = '<>';
                $position = array($this->{static::POSITION}-1, $this->{static::POSITION}+1);
        }

        if ($queryAll === true)
        {
            $query->where(static::POSITION, $operand, $this->{static::POSITION});
        }
        else
        {
            if ($direction == 'both')
            {
                $query->whereIn(static::POSITION, $position);
            }
            else
            {
                $query->where(static::POSITION, '=', $position);
            }
        }

        return $query;
    }

    /**
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
     * Grabs an immediate previous model sibling.
     *
     * @return Entity
     */
    public function prevSibling()
    {
        return $this->siblings('one', 'prev');
    }

    /**
     * Grabs collection of previous model siblings.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevSiblings()
    {
        return $this->siblings('all', 'prev');
    }

    /**
     * @return bool
     */
    public function hasPrevSiblings()
    {
        return !!$this->countPrevSiblings();
    }

    /**
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
     * Grabs and immediate next model sibling.
     *
     * @return Entity
     */
    public function nextSibling()
    {
        return $this->siblings('one', 'next');
    }

    /**
     * Grabs collection of next model siblings.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextSiblings()
    {
        return $this->siblings('all', 'next');
    }

    /**
     * @return bool
     */
    public function hasNextSiblings()
    {
        return !!$this->countNextSiblings();
    }

    /**
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
     * @param string $find 'one' for the first found, 'all' for collection of siblings
     * @param string $direction 'prev' for previous siblings, 'next' for next ones
     * @return \Illuminate\Database\Eloquent\Collection|Entity
     */
    public function siblings($find = 'all', $direction = 'both')
    {
        switch($find)
        {
            case 'one':
                $result = $this->buildSiblingsQuery($direction, false);

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
                $result = $this->buildSiblingsQuery($direction)->get();
                break;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function hasSiblings()
    {
        return !!$this->countSiblings();
    }

    /**
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
     * Checks whether the model is top level one.
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
     * @param bool $returnObjectsArray
     * @return array
     */
    public static function tree($returnObjectsArray = false)
    {
        return array();
    }

    /**
     * @param Entity $ancestor
     * @param int $position
     * @return Entity
     */
    public function moveTo(Entity $ancestor = null, $position = null)
    {
        return static::moveGivenTo($this, $ancestor, $position);
    }

    /**
     * Makes given model a child or a root.
     *
     * @param Entity|int $given
     * @param Entity|int|null $to
     * @param int|null $position
     * @return Entity
     */
    public static function moveGivenTo(Entity $given, Entity $to = null, $position = null)
    {
        $position = (int)$position;
        $oldPosition = $given->{static::POSITION};

        if ($position === $oldPosition)
        {
            return $given;
        }

        $given->{static::POSITION} = $position;
        $given->save();
        $given->performSiblingsReorder($oldPosition);

        if ($to === null)
        {
            $given->performMoveTo();

            return $given;
        }

        $given->performMoveTo($to->getKey());

        return $given;
    }

    /**
     * @param int|null $ancestorId
     * @return bool
     */
    protected function performMoveTo($ancestorId = null)
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
        if ($ancestorId === null)
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
     * @param int $oldPosition
     */
    protected function performSiblingsReorder($oldPosition)
    {
        $subquery = $this->buildSiblingsSubquery();
        $newPosition = $this->{static::POSITION};

        if ($subquery->count())
        {
            $range = range($newPosition, $oldPosition);
            $subquery = $subquery->whereIn(static::POSITION, $range);

            if ($newPosition < $oldPosition || $newPosition == $oldPosition)
            {
                $subquery->increment(static::POSITION);
            }
            else
            {
                $subquery->decrement(static::POSITION);
            }
        }
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @return bool
     */
    public function performInsert($query)
    {
        if (parent::performInsert($query) === true)
        {
            $id = $this->getKey();
            $parent = $this->parent();

            if ( ! $parent instanceof Entity)
            {
                $parentId = $id;
            }
            else
            {
                $parentId = $parent->getKey();
            }

            $this->performInsertNode($id, $parentId);
            $this->performSiblingsReorder($this->{static::POSITION});

            return true;
        }

        return false;
    }

    public function performUpdate($query)
    {
        return parent::performUpdate($query);
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