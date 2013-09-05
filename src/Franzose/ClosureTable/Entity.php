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

/**
 * Generic model with Closure Table database design pattern capabilities.
 */
class Entity extends Eloquent {

    /**
     * Closure table name for this model.
     *
     * It is used to avoid spawning of ClosureTable models
     * (via extension of the base model) for each new Entity model.
     * As you can see, the only one ClosureTable model is used Entity models.
     *
     * @var string
     * @see Entity::closuretable()
     */
    protected static $closure;

    /**
     * Static alias for Eloquent::getQualifiedKeyName() method results.
     *
     * @var string
     * @see Entity::roots()
     */
    protected static $qualifiedKeyName;

    /**
     * A sort of 'virtual' attribute of the direct parent identifier.
     *
     * @var Entity
     * @see Entity::appendChild()
     */
    protected $parent = null;

    /**
     * The position column name.
     *
     * @var string
     */
    const POSITION = 'position';

    /**
     * Model constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        ClosureTable::$tableName  = static::$closure;
        static::$qualifiedKeyName = $this->getQualifiedKeyName();
    }

    /**
     * Relation to ClosureTable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    protected function closuretable()
    {
        return $this->hasOne('Franzose\ClosureTable\ClosureTable', ClosureTable::DESCENDANT);
    }

    /**
     * Gets parent identifier virtual attribute.
     *
     * @return Entity
     */
    protected function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets parent identifier virtual attribute.
     *
     * @param Entity $value
     * @return Entity
     */
    protected function setParent(Entity $value = null)
    {
        $this->parent = $value;

        return $this;
    }

    /**
     * Gets direct model parents.
     *
     * @return \Illuminate\Database\Eloquent\Collection|static
     */
    public function parents()
    {
        return $this->buildParentsQuery()->get();
    }

    /**
     * Gets the first direct model parent.
     *
     * @return mixed|null
     */
    public function parent()
    {
        return $this->parents()->first();
    }

    /**
     * Checks whether the model has direct parents.
     *
     * @return bool
     */
    public function hasParents()
    {
        return !!$this->countParents();
    }

    /**
     * Counts direct model parents.
     *
     * @return int
     */
    protected function countParents()
    {
        return $this->buildParentsQuery()->count();
    }

    /**
     * Builds query for the direct model parents.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildParentsQuery()
    {
        $depth = $this->closuretable->{ClosureTable::DEPTH};
        $depth = ($depth <= 1 ? $depth : $depth-1);

        $dk = ClosureTable::getQualifiedDescendantKeyName();
        $dpk = ClosureTable::getQualifiedDepthKeyName();

        return $this->join(static::$closure, $dk, '=', $this->getQualifiedKeyName())
            ->where($dk, '=', $this->getKey())
            ->where($dpk, '=', $depth);
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
        return $this->buildAncestorsQuery()->count();
    }

    /**
     * Builds query for the model parents.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildAncestorsQuery()
    {
        $ak = ClosureTable::getQualifiedAncestorKeyName();
        $dk = ClosureTable::getQualifiedDescendantKeyName();
        $dpk = ClosureTable::getQualifiedDepthKeyName();

        return $this->join(static::$closure, $ak, '=', $this->getQualifiedKeyName())
            ->where($dk, '=', $this->getKey())
            ->where($dpk, '>', 0);
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
        return $this->buildChildrenQuery()->count();
    }

    /**
     * Builds query for the direct model children.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildChildrenQuery()
    {
        $ak = ClosureTable::getQualifiedAncestorKeyName();
        $dk = ClosureTable::getQualifiedDescendantKeyName();
        $dpk = ClosureTable::getQualifiedDepthKeyName();

        return $this->join(static::$closure, $dk, '=', $this->getQualifiedKeyName())
            ->where($ak, '=', $this->getKey())
            ->where($dpk, '=', $this->closuretable->{ClosureTable::DEPTH}+1);
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
        return $this->buildDescendantsQuery()->count();
    }

    /**
     * @return array
     * @see Entity::delete()
     * @see Entity::deleteDescendants()
     */
    protected function getDescendantsIds()
    {
        return $this->buildDescendantsQuery()->lists($this->getKeyName());
    }

    /**
     * Builds query for the model children.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildDescendantsQuery()
    {
        $ak = ClosureTable::getQualifiedAncestorKeyName();
        $dk = ClosureTable::getQualifiedDescendantKeyName();
        $dpk = ClosureTable::getQualifiedDepthKeyName();

        return $this->join(static::$closure, $dk, '=', $this->getQualifiedKeyName())
            ->where($ak, '=', $this->getKey())
            ->where($dpk, '>', 0);
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
     * @return array
     */
    public function prevSiblings()
    {
        return $this->siblings('all', 'prev');
    }

    /**
     * Grabs and immediate next model sibling.
     *
     * @return Entity
     */
    public function nextSibling()
    {
        return $this->siblings('one');
    }

    /**
     * Grabs collection of next model siblings.
     *
     * @return array
     */
    public function nextSiblings()
    {
        return $this->siblings();
    }

    /**
     * Retrieves previous or next model siblings.
     *
     * @param string $find 'one' for the first found, 'all' for collection of siblings
     * @param string $direction 'prev' for previous siblings, 'next' for next ones
     * @return mixed
     */
    protected function siblings($find = 'all', $direction = 'next')
    {
        $position = null;

        switch($direction)
        {
            case 'prev':
                $position = $this->{static::POSITION}-1;
                break;

            case 'next':
                $position = $this->{static::POSITION}+1;
                break;
        }

        $query = $this->select(array($this->table.'.*'))
            ->join(static::$closure, ClosureTable::getQualifiedDescendantKeyName(), '=', $this->getQualifiedKeyName())
            ->where(ClosureTable::getQualifiedDepthKeyName(), '=', $this->closuretable->{ClosureTable::DEPTH})
            ->where(static::POSITION, '=', $position);

        switch($find)
        {
            case 'one':
                $result = $query->first();
                break;

            case 'all':
                $result = $query->get();
                break;
        }

        return $result;
    }

    /**
     * Retrieves all models with depth equal 0.
     *
     * @return array
     */
    public static function roots()
    {
        return static::buildRootsQuery()->get();
    }

    /**
     * Checks whether the model is top level one.
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->depth == 0 || !$this->buildAncestorsQuery()->count();
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function buildRootsQuery()
    {
        return static::join(static::$closure, function($join){
            $pk = static::$qualifiedKeyName;
            $join->on(ClosureTable::getQualifiedAncestorKeyName(), '=', $pk);
            $join->on(ClosureTable::getQualifiedDescendantKeyName(), '=', $pk);
        })->where(ClosureTable::getQualifiedDepthKeyName(), '=', 0);
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
        $this->setParent($ancestor);
        $closuretable = $this->closuretable;

        if ($ancestor === null)
        {
            if ($position !== null && is_int($position))
            {
                $this->{static::POSITION} = $position;
                $this->save();
            }

            $closuretable->moveTo();

            return $this;
        }

        $ancestorClosure = $ancestor->closuretable;
        $ancestorValue = $ancestorClosure->{ClosureTable::ANCESTOR};
        $depthValue = $ancestorClosure->{ClosureTable::DEPTH};

        $closuretable->{ClosureTable::DEPTH} = $depthValue;
        $closuretable->save();
        $closuretable->moveTo($ancestorValue);

        if ($position !== null && is_int($position))
        {
            $this->{static::POSITION} = $position;
            $this->save();
        }

        return $this;
    }

    /**
     * Makes given model a child or a root.
     *
     * @param Entity|array|int $given
     * @param Entity|array|int|null $to
     * @param int $position
     */
    public static function moveGivenTo($given, $to, $position = 0)
    {
        //
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
        if ($child->getKey() === null)
        {
            $child->{static::POSITION} = (int)$position;
            $child->setParent($this)->save();
        }
        else
        {
            $child->moveTo($this, (int)$position);
        }

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
        if ($position === null)
            $position = 0;

        $this->buildChildrenQuery()->where(static::POSITION, '=', $position)->delete();

        return $this;
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
            $parent = $this->getParent();

            if ( ! $parent instanceof Entity)
            {
                $parentId = $id;
            }
            else
            {
                $parentId = $parent->getKey();
            }

            ClosureTable::insertLeaf($id, $parentId);
            return true;
        }

        return false;
    }

    /**
     * Deletes the model from the database.
     * Also corrects closure table data of its descendants if those exist.
     *
     * @return bool|null|void
     */
    public function delete()
    {
        static::moveGivenTo($this->getDescendantsIds(), null);
        $this->closuretable->delete();

        parent::delete();
    }

    /**
     * Delete the model, all related models and relationships in the closure table.
     *
     * @return bool|null|void
     */
    public function deleteSubtree()
    {
        $this->deleteDescendants();
        $this->closuretable->deleteSubtree();
        return $this->delete();
    }

    /**
     * Deletes all model descendants.
     *
     * @return int|bool
     */
    protected function deleteDescendants()
    {
        return $this->whereIn($this->getKeyName(), $this->getDescendantsIds())->delete();
    }
}