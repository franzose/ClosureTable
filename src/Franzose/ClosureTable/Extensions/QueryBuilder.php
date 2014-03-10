<?php namespace Franzose\ClosureTable\Extensions;

use \Illuminate\Database\ConnectionInterface;
use \Illuminate\Database\Query\Grammars\Grammar;
use \Illuminate\Database\Query\Processors\Processor;

/**
 * Extended QueryBuilder class. Provides methods for building ClosureTable specific queries.
 *
 * @package Franzose\ClosureTable\Extensions
 */
class QueryBuilder extends \Illuminate\Database\Query\Builder {

    /**
     * Various entity and closure attributes.
     *
     * @var array
     */
    protected $qattrs;

    /**
     * 'Find all but self' flag.
     *
     * @var int
     */
    const ALL_BUT_SELF  = 1;

    /**
     * 'Find all including self' flag.
     *
     * @var int
     */
    const ALL_INC_SELF  = 2;

    /**
     * 'Find neighbors' flag.
     *
     * @var int
     */
    const NEIGHBORS     = 3;

    /**
     * 'Find one previous sibling' flag.
     *
     * @var int
     */
    const PREV_ONE      = 4;

    /**
     * 'Find all previous siblings' flag.
     *
     * @var int
     */
    const PREV_ALL      = 5;

    /**
     * 'Find one next sibling' flag.
     *
     * @var int
     */
    const NEXT_ONE      = 6;

    /**
     * 'Find all next siblings' flag.
     *
     * @var int
     */
    const NEXT_ALL      = 7;

    /**
     * 'Find within range' flag.
     *
     * @var int
     */
    const IN_RANGE      = 8;

    /**
     * 'Find using where in clause' flag.
     *
     * @var int
     */
    const BY_WHERE_IN   = 9;

    /**
     * 'Find using join clause' flag.
     *
     * @var int
     */
    const BY_JOIN       = 10;

    /**
     * Create a new query builder instance.
     *
     * @param ConnectionInterface $connection
     * @param Grammar $grammar
     * @param Processor $processor
     * @param array $queriedAttributes
     */
    public function __construct(ConnectionInterface $connection,
                                Grammar $grammar,
                                Processor $processor,
                                array $queriedAttributes = [])
    {
        parent::__construct($connection, $grammar, $processor);

        $this->qattrs = $queriedAttributes;
    }

    /**
     * Builds parent query.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function parent(array $columns = ['*'])
    {
        return $this->select($columns)
            ->join($this->qattrs['closure'], $this->qattrs['ancestor'], '=', $this->qattrs['pk'])
            ->where($this->qattrs['descendant'], '=', $this->qattrs['pkValue'])
            ->where($this->qattrs['depth'], '=', 1);
    }

    /**
     * Builds ancestors query.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function ancestors(array $columns = ['*'])
    {
        return $this->select($columns)
            ->join($this->qattrs['closure'], $this->qattrs['ancestor'], '=', $this->qattrs['pk'])
            ->where($this->qattrs['descendant'], '=', $this->qattrs['pkValue'])
            ->where($this->qattrs['depth'], '>', 0);
    }

    /**
     * Determines query method by given 'type'.
     *
     * @param string $method
     * @param int $type
     * @return string
     */
    protected function getQueryMethodType($method, $type)
    {
        return $method . ($type == static::BY_WHERE_IN ? 'Subquery' : 'Join');
    }


    /**
     * Builds descendants query.
     *
     * @param array $columns
     * @param int $what
     * @param int $type
     * @return QueryBuilder
     */
    public function descendants(array $columns = ['*'], $what = self::ALL_BUT_SELF, $type = self::BY_JOIN)
    {
        $depthValue = 0;

        switch($what)
        {
            case static::ALL_INC_SELF:
                $depthOperator = '>=';
                break;

            default:
                $depthOperator = '>';
                break;
        }

        $method = $this->getQueryMethodType('descendantsBy', $type);

        return $this->{$method}($columns, $depthOperator, $depthValue);
    }

    /**
     * Builds descendants query using inner join.
     *
     * @param array $columns
     * @param string $depthOperator
     * @param int $depthValue
     * @return QueryBuilder
     */
    protected function descendantsByJoin(array $columns = ['*'], $depthOperator, $depthValue)
    {
        $query = $this->select($columns)
            ->join($this->qattrs['closure'], $this->qattrs['descendant'], '=', $this->qattrs['pk'])
            ->where($this->qattrs['ancestor'], '=', $this->qattrs['pkValue'])
            ->where($this->qattrs['depth'], $depthOperator, $depthValue);

        return $query;
    }

    /**
     * Builds descendants query using sub-select query.
     *
     * @param array $columns
     * @param string $depthOperator
     * @param int $depthValue
     * @return QueryBuilder
     */
    protected function descendantsBySubquery(array $columns = ['*'], $depthOperator, $depthValue)
    {
        return $this->select($columns)
            ->whereIn($this->qattrs['pk'], function(QueryBuilder $q) use($depthOperator, $depthValue)
            {
                $q->select($this->qattrs['descendantShort'])
                  ->from($this->qattrs['closure'])
                  ->where($this->qattrs['ancestorShort'], '=', $this->qattrs['pkValue'])
                  ->where($this->qattrs['depthShort'], $depthOperator, $depthValue);
            });
    }

    /**
     * Builds descendants query that includes the ancestor.
     *
     * @param array $columns
     * @param int $type
     * @return QueryBuilder
     */
    public function descendantsWithSelf(array $columns = ['*'], $type = self::BY_JOIN)
    {
        return $this->descendants($columns, self::ALL_INC_SELF, $type);
    }

    /**
     * Builds children query.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function children(array $columns = ['*'])
    {
        return $this->where($this->qattrs['parentIdShort'], '=', $this->qattrs['pkValue']);
    }

    /**
     * Builds a child query with given position.
     *
     * @param $position
     * @param array $columns
     * @return QueryBuilder
     */
    public function childAt($position, array $columns = ['*'])
    {
        return $this->children($columns)->where($this->qattrs['position'], '=', $position);
    }

    /**
     * Builds the last child query.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function lastChild(array $columns = ['*'])
    {
        return $this->children($columns)->orderBy($this->qattrs['position'], 'desc');
    }

    /**
     * Performs removing a child with given position.
     *
     * @param int $position
     * @param bool $forceDelete
     * @return bool
     */
    public function removeChildAt($position, $forceDelete = false)
    {
        $action = ($forceDelete === true ? 'forceDelete' : 'delete');
        $columns = [$this->qattrs['pk'], $this->qattrs['position']];

        return $this->childAt($position, $columns)->$action();
    }

    /**
     * Performs removing children within a range of positions.
     *
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     * @return bool
     */
    public function removeChildrenRange($from, $to = null, $forceDelete = false)
    {
        $query = $this->children([$this->qattrs['pk'], $this->qattrs['position']])
            ->where($this->qattrs['position'], '>=', $from);

        if ( ! is_null($to))
        {
            $query->where($this->qattrs['position'], '<=', $to);
        }

        $action = ($forceDelete === true ? 'forceDelete' : 'delete');

        return $query->$action();
    }

    /**
     * Builds various siblings query.
     *
     * @param array $columns
     * @param int $what
     * @param int $type
     * @return QueryBuilder
     */
    protected function getSiblingsQuery(array $columns = ['*'], $what = self::ALL_BUT_SELF, $type = self::BY_JOIN)
    {
        $args = [
            'operand'  => $this->getOperandForSiblingsQuery($what),
            'position' => $this->getPositionForSiblingsQuery($what)
        ];

        $method = $this->getQueryMethodType('siblingsBy', $type);

        $query = $this->{$method}($columns, $what);

        if ($this->qattrs['depthValue'] == 0)
        {
            $this->whereNull($this->qattrs['parentIdShort']);
        }

        switch($what)
        {
            case static::NEIGHBORS:
                $query->whereIn($this->qattrs['position'], $args['position']);
                break;

            case static::PREV_ONE:
            case static::PREV_ALL:
            case static::NEXT_ONE:
            case static::NEXT_ALL:
                $query->where($this->qattrs['position'], $args['operand'], $args['position']);
                break;
        }

        return $query;
    }

    /**
     * Determines operand that is used in 'where position' clause.
     *
     * @param int $what
     * @return string
     */
    protected function getOperandForSiblingsQuery($what)
    {
        switch($what)
        {
            case static::ALL_BUT_SELF:
                return '<>';

            case static::PREV_ALL:
                return '<';

            case static::NEXT_ALL:
                return '>';

            default:
                return '=';
        }
    }

    /**
     * Determines position that is used in 'where position' clause.
     *
     * @param int $what
     * @return mixed
     */
    protected function getPositionForSiblingsQuery($what)
    {
        switch($what)
        {
            case static::PREV_ONE:
                return $this->qattrs['positionValue']-1;

            case static::NEXT_ONE:
                return $this->qattrs['positionValue']+1;

            case static::NEIGHBORS:
                return [
                    $this->qattrs['positionValue']-1,
                    $this->qattrs['positionValue']+1
                ];

            default:
                return $this->qattrs['positionValue'];
        }
    }

    /**
     * Builds siblings query made by inner join.
     *
     * @param array $columns
     * @param int $type
     * @return QueryBuilder
     */
    protected function siblingsByJoin($columns, $type)
    {
        $query = $this->select($columns)
            ->join($this->qattrs['closure'].' as c',
                   'c.'.$this->qattrs['descendantShort'],
                   '=',
                   $this->qattrs['pk'])
            ->finalizeSiblingsQuery($type);

        return $query;
    }

    /**
     * Builds siblings query made by subquery.
     *
     * @param array $columns
     * @param int $type
     * @return QueryBuilder
     */
    protected function siblingsBySubquery($columns, $type)
    {
        $query = $this->select($columns)
            ->whereIn($this->qattrs['pk'], function(QueryBuilder $q) use($type)
            {
                $q->select($this->qattrs['descendantShort'])
                  ->from($this->qattrs['closure'].' as c')
                  ->finalizeSiblingsQuery($type);
            });

        return $query;
    }

    /**
     * Incapsulates a part that is repeated part in both types of siblings queries.
     *
     * @param int $type
     * @return QueryBuilder
     */
    protected function finalizeSiblingsQuery($type)
    {
        $this->where('c.'.$this->qattrs['depthShort'], '=', $this->qattrs['depthValue']);

        if ($type != static::IN_RANGE)
        {
            $this->where('c.'.$this->qattrs['ancestorShort'], '=', $this->qattrs['ancestorValue']);
        }

        if ($type == static::IN_RANGE || $type == static::ALL_BUT_SELF)
        {
            $this->where('c.'.$this->qattrs['descendantShort'], '<>', $this->qattrs['pkValue']);
        }

        return $this;
    }


    /**
     * Wrapper for siblings query builder method.
     *
     * @param array $columns
     * @param int $what
     * @param int $type
     * @return QueryBuilder
     */
    public function siblings(array $columns = ['*'], $what = self::ALL_BUT_SELF, $type = self::BY_JOIN)
    {
        return $this->getSiblingsQuery($columns, $what, $type);
    }

    /**
     * Builds a query for a sibling with given position.
     *
     * @param int $position
     * @param array $columns
     * @return QueryBuilder
     */
    public function siblingAt($position, array $columns = ['*'])
    {
        return $this->siblings($columns)->where($this->qattrs['position'], '=', $position);
    }

    /**
     * Builds query for the last sibling.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function lastSibling(array $columns = ['*'])
    {
        return $this->siblings($columns)->orderBy($this->getPositionColumn(), 'desc');
    }

    /**
     * Builds neighbors query.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function neighbors(array $columns = ['*'])
    {
        return $this->getSiblingsQuery($columns, static::NEIGHBORS);
    }

    /**
     * Builds a query for all previous siblings.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function prevSiblings(array $columns = ['*'])
    {
        return $this->getSiblingsQuery($columns, static::PREV_ALL);
    }

    /**
     * Builds a query for the previous sibling.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function prevSibling(array $columns = ['*'])
    {
        return $this->getSiblingsQuery($columns, static::PREV_ONE);
    }

    /**
     * Builds a query for all next siblings.
     *
     * @param array $columns
     * @param int $type
     * @return QueryBuilder
     */
    public function nextSiblings(array $columns = ['*'], $type = self::BY_JOIN)
    {
        return $this->getSiblingsQuery($columns, static::NEXT_ALL, $type);
    }

    /**
     * Builds a query for the next sibling.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function nextSibling(array $columns = ['*'])
    {
        return $this->getSiblingsQuery($columns, static::NEXT_ONE);
    }

    /**
     * Builds a query for siblings within a range of positions.
     *
     * @param array|int $rangeOrPos
     * @param int $type
     * @return QueryBuilder
     */
    public function siblingsRange($rangeOrPos, $type = self::BY_JOIN)
    {
        $query = $this->siblings([$this->qattrs['pk'], $this->qattrs['position']], static::IN_RANGE, $type);

        if (is_array($rangeOrPos))
        {
            $query->whereIn($this->qattrs['position'], $rangeOrPos);
        }
        else
        {
            $query->where($this->qattrs['position'], '>=', $rangeOrPos);
        }

        return $query;
    }

    /**
     * Builds roots query.
     *
     * @param array $columns
     * @return QueryBuilder
     */
    public function roots(array $columns = ['*'])
    {
        return $this->select($columns)->whereNull($this->qattrs['parentIdShort']);
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return \Franzose\ClosureTable\Extensions\QueryBuilder
     */
    public function newQuery()
    {
        return new static($this->connection, $this->grammar, $this->processor, $this->qattrs);
    }
} 