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
 * Generic Closure Table model.
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

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return Entity|static
     */
    public static function create(array $attributes)
    {
        $model = parent::create($attributes);
        $model->setHidden($model->getClosureAttributes());
        return $model;
    }

    /**
     * Get the closure table associated with the model.
     *
     * @return string
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Get closure table column values on the model.
     *
     * @return array
     */
    protected function getClosureAttributes()
    {
        $closure = DB::table($this->getClosure())->where(static::DESCENDANT, '=', $this->getKey());
        $depth   = $closure->max(static::DEPTH);
        $columns = array(static::ANCESTOR, static::DESCENDANT, static::DEPTH);

        return (array)$closure->where(static::DEPTH, '=', $depth)->first($columns);
    }

    /**
     * Get ancestor attribute from the model.
     *
     * @return int
     */
    protected function getAncestor()
    {
        return $this->hidden[static::ANCESTOR];
    }

    /**
     * Get descendant attribute from the model.
     *
     * @return int
     */
    protected function getDescendant()
    {
        return $this->hidden[static::DESCENDANT];
    }

    /**
     * Get depth attribute on the model.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->hidden[static::DEPTH];
    }

    /**
     * Get direct model ancestor.
     *
     * @return Entity|null
     */
    public function parent()
    {
        return $this->select(array($this->getTable().'.*'))
            ->join($this->getClosure(), $this->getQualifiedAncestorKeyName(), '=', $this->getQualifiedKeyName())
            ->where($this->getQualifiedDescendantKeyName(), '=', $this->getKey())
            ->where($this->getQualifiedDepthKeyName(), '=', 1)
            ->first();
    }

    /**
     * Build query for the model ancestors.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildAncestorsQuery()
    {
        $ak = $this->getQualifiedAncestorKeyName();
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->select($this->getTable().'.*')
            ->join($this->getClosure(), $ak, '=', $this->getQualifiedKeyName())
            ->where($dk, '=', $this->getKey())
            ->where($dpk, '>', 0);
    }

    /**
     * Get all model ancestors.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ancestors()
    {
        return $this->buildAncestorsQuery()->get();
    }

    /**
     * Check if the model has any ancestors.
     *
     * @return bool
     */
    public function hasAncestors()
    {
        return !!$this->countAncestors();
    }

    /**
     * Count model ancestors.
     *
     * @return int
     */
    public function countAncestors()
    {
        return (int)$this->buildAncestorsQuery()->count();
    }

    /**
     * Build query for the direct model descendants.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildChildrenQuery()
    {
        $ak = $this->getQualifiedAncestorKeyName();
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->join($this->getClosure(), $dk, '=', $this->getQualifiedKeyName())
            ->where($ak, '=', $this->getKey())
            ->where($dpk, '=', 1);
    }

    /**
     * Get direct model descendants.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        $result = (isset($this->nested) ? $this->nested : $this->buildChildrenQuery()->get());

        return $result;
    }

    /**
     * Check if the model has direct descendants.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return !!$this->countChildren();
    }

    /**
     * Count direct model descendants.
     *
     * @return int
     */
    public function countChildren()
    {
        $result = (isset($this->nested) ? (int)$this->nested->count() : (int)$this->buildChildrenQuery()->count());

        return $result;
    }

    /**
     * Get the first direct descendant of the model.
     *
     * @return Entity
     */
    public function firstChild()
    {
        return $this->childAt(0);
    }

    /**
     * Get the last direct descendant of the model.
     *
     * @return Entity
     */
    public function lastChild()
    {
        $max = $this->buildChildrenQuery()->max(static::POSITION);
        return $this->childAt($max);
    }

    /**
     * Get direct descendant at given position.
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
     * Insert a model as a direct descendant of this one.
     *
     * @param Entity $child
     * @param int|null $position
     * @param bool $returnChild
     * @return Entity
     */
    public function appendChild(Entity $child, $position = null, $returnChild = false)
    {
        $child->moveTo($this, $position);

        return ($returnChild === true ? $child : $this);
    }

    /**
     * Remove a direct descendant with given position.
     *
     * @param int|null $position
     * @param bool $forceDelete
     * @return Entity
     */
    public function removeChild($position = null, $forceDelete = false)
    {
        $child = null;
        $position = (int)$position;

        if (isset($this->nested) && isset($this->nested[$position]))
        {
            $child = $this->nested[$position];
        }
        else
        {
            $child = $this->buildChildrenQuery()->where(static::POSITION, '=', (int)$position)->first();
        }

        if ($child !== null)
        {
            if ($forceDelete === true)
            {
                $child->forceDelete();
            }
            else
            {
                $child->delete();
            }
        }

        return $this;
    }

    /**
     * Build query for the model descendants.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildDescendantsQuery()
    {
        $ak = $this->getQualifiedAncestorKeyName();
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->join($this->getClosure(), $dk, '=', $this->getQualifiedKeyName())
            ->where($ak, '=', $this->getKey())
            ->where($dpk, '>', 0);
    }

    /**
     * Get all model descendants.
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
     * Check if the model has any descendants.
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
     * Build query for siblings of the model.
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
     * Build a part of the siblings query.
     * This part defines a sibling regardless of direction (prev or next) and position.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSiblingsSubquery()
    {
        $dk = $this->getQualifiedDescendantKeyName();
        $dpk = $this->getQualifiedDepthKeyName();

        return $this->select(array($this->getTable().'.*'))
            ->join($this->getClosure(), $dk, '=', $this->getQualifiedKeyName())
            ->where($dk, '<>', $this->getKey())
            ->where($dpk, '=', $this->getDepth());
    }

    /**
     * Retrieve previous or next model siblings.
     *
     * @param string $find number of the searched: 'all', 'one'
     * @param string $direction searching direction: 'prev', 'next', 'both'
     * @param int|null $position
     * @return \Illuminate\Database\Eloquent\Collection|Entity
     */
    public function siblings($find = 'all', $direction = 'both', $position = null)
    {
        $position = ($position === null ? $this->{static::POSITION} : $position);

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
     * Get the first sibling of a model.
     *
     * @return Entity
     */
    public function firstSibling()
    {
        return $this->siblingAt(0);
    }

    /**
     * Get the last sibling of a model.
     *
     * @return Entity
     */
    public function lastSibling()
    {
        $lastpos = $this->buildSiblingsSubquery()->max(static::POSITION);
        return $this->siblingAt($lastpos);
    }

    /**
     * Get a sibling with given position.
     *
     * @param $position
     * @return Entity
     */
    public function siblingAt($position)
    {
        return $this->siblings('one', 'next', $position-1);
    }

    /**
     * Get a previous model sibling.
     *
     * @return Entity
     */
    public function prevSibling()
    {
        return $this->siblings('one', 'prev');
    }

    /**
     * Get collection of previous model siblings.
     *
     * @param int|null $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevSiblings($position = null)
    {
        return $this->siblings('all', 'prev', $position);
    }

    /**
     * Check if the model has previous siblings.
     *
     * @return bool
     */
    public function hasPrevSiblings()
    {
        return !!$this->countPrevSiblings();
    }

    /**
     * Count previous siblings.
     *
     * @return int
     */
    public function countPrevSiblings()
    {
        return (int)$this->buildSiblingsQuery('prev')->count();
    }

    /**
     * Get the next model sibling.
     *
     * @return Entity
     */
    public function nextSibling()
    {
        return $this->siblings('one', 'next');
    }

    /**
     * Get collection of the next model siblings.
     *
     * @param int|null $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextSiblings($position = null)
    {
        return $this->siblings('all', 'next', $position);
    }

    /**
     * Check if model has next siblings.
     *
     * @return bool
     */
    public function hasNextSiblings()
    {
        return !!$this->countNextSiblings();
    }

    /**
     * Count next siblings.
     *
     * @return int
     */
    public function countNextSiblings()
    {
        return (int)$this->buildSiblingsQuery('next')->count();
    }

    /**
     * Check if model has siblings.
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return !!$this->countSiblings();
    }

    /**
     * Count model siblings.
     *
     * @return int
     */
    public function countSiblings()
    {
        return (int)$this->buildSiblingsQuery('both', true, $this->{static::POSITION})->count();
    }

    /**
     * Retrieve all models that have no ancestors.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function roots()
    {
        $instance   = new static;
        $table      = $instance->getTable();
        $closure    = $instance->getClosure();
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
     * Check if model is a top level one.
     *
     * @return bool
     */
    public function isRoot()
    {
        return !!DB::table($this->getClosure())
                      ->where(static::DESCENDANT, '=', $this->getKey())
                      ->where(static::DEPTH, '>', 0)
                      ->count() == 0;
    }

    /**
     * Move the model and its relationships to the top level of the tree.
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
     * Retrive a whole tree from the database.
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
            ->join($instance->getClosure().' as closure1', $key, '=', 'closure1.'.static::ANCESTOR)
            ->join($instance->getClosure().' as closure2', $key, '=', 'closure2.'.static::DESCENDANT)
            ->whereRaw('closure1.'.static::ANCESTOR.' = closure1.'.static::DESCENDANT)
            ->get()
            ->toTree();
    }

    /**
     * Make the model a root or a direct descendant of the given model.
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
     * Make given model a root or a direct descendant of another model.
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

        $given->{static::POSITION} = $given->guessPositionOnMoveTo($to, $position);

        if ($given->exists)
        {
            $given->performMoveTo($to);
            $given->save();
        }
        else
        {
            $given->save();
            $given->performMoveTo($to);
        }

        return $given;
    }

    /**
     * Set a proper position before moving the model.
     *
     * @param Entity|null $to
     * @param $position
     * @return int|mixed
     */
    protected function guessPositionOnMoveTo(Entity $to = null, $position)
    {
        if ($position === null)
        {
            $lastSibling = ($to === null ? null : $to->lastChild());
            $position = ($lastSibling === null ? 0 : $lastSibling->{static::POSITION}+1);
        }
        elseif ($position > 0 && $to->hasChildren() === false)
        {
            $position = 0;
        }

        return $position;
    }

    /**
     * Reorder all of the model siblings when it's moved.
     * When the model is moved from one subtree to another, its 'old' siblings are reordered as well.
     *
     * @param Entity $oldStateEntity
     * @return void
     */
    protected function reorderSiblings(Entity $oldStateEntity = null)
    {
        if ($oldStateEntity !== null && $oldStateEntity->hasSiblings())
        {
            $origpos = $oldStateEntity->getOriginal('position');

            if ($this->{static::POSITION} != $origpos)
            {
                $keyName = $this->getKeyName();

                $siblingsIds = $this->buildSiblingsSubquery()
                    ->where($this->getQualifiedKeyName(), '<>', $this->getKey())
                    ->lists($keyName);

                $siblings = $this->whereIn($keyName, $siblingsIds);

                if ($this->{static::POSITION} > $origpos)
                {
                    $action = 'decrement';
                    $range = range($origpos, $this->{static::POSITION});
                }
                else
                {
                    $action = 'increment';
                    $range = range($this->{static::POSITION}, $origpos-1);
                }

                $siblings->whereIn(static::POSITION, $range)->$action(static::POSITION);

                // here we reorder model's 'old' siblings
                $oldStateEntityDepth = $oldStateEntity->getDepth();

                if ($this->getDepth() != $oldStateEntityDepth)
                {
                    $nextIds = $oldStateEntity->nextSiblings()->lists($keyName);
                    $oldStateEntity->whereIn($keyName, $nextIds)->decrement(static::POSITION);
                }
            }
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
            $this->setHidden($this->getClosureAttributes());

            return true;
        }

        return false;
    }

    /**
     * Perform a model update operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @return bool
     */
    protected function performUpdate($query)
    {
        $oldStateEntity = $this;

        if (parent::performUpdate($query) === true)
        {
            $this->setHidden($this->getClosureAttributes());
            $this->reorderSiblings($oldStateEntity);
        }
    }

    /**
     * Perform closure table rebuilding when the model's moved.
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

        // prevent constraint errors
        if ($ancestor !== null && $ancestorValue === $ancestor->getKey())
        {
            return;
        }

        $descendantValue = $this->getKey();
        $closure = $this->getClosure();

        $ancestorsIds = DB::table($closure)
            ->where($dk, '=', $descendantValue)
            ->where($ak, '<>', $descendantValue)
            ->lists($ak);

        $descendantsIds = DB::table($closure)
            ->where($dk, '=', $descendantValue)
            ->lists($dk);

        if (count($ancestorsIds))
        {
            DB::table($closure)
                ->whereIn($dk, $descendantsIds)
                ->whereIn($ak, $ancestorsIds)
                ->delete();
        }

        // null? make it root
        if ($ancestor === null)
        {
            return DB::table($closure)
                ->where(static::ANCESTOR, '=', $ancestorValue)
                ->where(static::DESCENDANT, '=', $descendantValue)
                ->update(array(
                    static::DEPTH => 0,
                    static::ANCESTOR => $descendantValue
            ));
        }

        $ancestorId = $ancestor->getKey();

        DB::transaction(function() use($ak, $dk, $dpk, $closure, $ancestorId, $descendantValue){
            $selectQuery = "
                SELECT supertbl.{$ak}, subtbl.{$dk}, supertbl.{$dpk}+subtbl.{$dpk}+1 as {$dpk}
                FROM {$closure} as supertbl
                CROSS JOIN {$closure} as subtbl
                WHERE supertbl.{$dk} = {$ancestorId}
                AND subtbl.{$ak} = {$descendantValue}
            ";

            $results = DB::select($selectQuery);
            array_walk($results, function(&$item){ $item = (array)$item; });

            DB::table($this->closure)->insert($results);
        });
    }

    /**
     * Perform closure table rebuilding when a new model is saved to the database.
     *
     * @param $descendant
     * @param $ancestor
     * @return mixed
     */
    protected function performInsertNode($descendant, $ancestor)
    {
        $table = $this->getClosure();
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
        return $this->getClosure().'.'.static::ANCESTOR;
    }

    /**
     * Get the table qualified descendant key name.
     *
     * @return string
     */
    protected function getQualifiedDescendantKeyName()
    {
        return $this->getClosure().'.'.static::DESCENDANT;
    }

    /**
     * Get the table qualified depth key name.
     *
     * @return string
     */
    protected function getQualifiedDepthKeyName()
    {
        return $this->getClosure().'.'.static::DEPTH;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new \Franzose\ClosureTable\Collection($models);
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = array())
    {
        $instance = $this->newInstance(array(), true);
        $instance->setRawAttributes((array) $attributes, true);
        $instance->setHidden($instance->getClosureAttributes());

        return $instance;
    }
}