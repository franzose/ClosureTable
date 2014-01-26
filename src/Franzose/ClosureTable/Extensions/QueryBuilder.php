<?php namespace Franzose\ClosureTable\Extensions;

use \Illuminate\Database\ConnectionInterface;
use \Illuminate\Database\Query\Grammars\Grammar;
use \Illuminate\Database\Query\Processors\Processor;
use \Franzose\ClosureTable\Contracts\EntityInterface;

/**
 * Class QueryBuilder
 * @package Franzose\ClosureTable\Extensions
 */
class QueryBuilder extends \Illuminate\Database\Query\Builder {

    /**
     * @var array
     */
    protected $qattrs;

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
                                array $queriedAttributes)
    {
        parent::__construct($connection, $grammar, $processor);

        $this->qattrs = $queriedAttributes;
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function parent(array $columns = ['*'])
    {
        return $this->select($columns)
            ->join($this->qattrs['closure'], $this->qattrs['ancestor'], '=', $this->qattrs['pk'])
            ->where($this->qattrs['descendant'], '=', $this->qattrs['pkValue'])
            ->where($this->qattrs['depth'], '=', 1);
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function ancestors(array $columns = ['*'])
    {
        return $this->select($columns)
            ->join($this->qattrs['closure'], $this->qattrs['ancestor'], '=', $this->qattrs['pk'])
            ->where($this->qattrs['descendant'], '=', $this->qattrs['pkValue'])
            ->where($this->qattrs['depth'], '>', 0);
    }

    /**
     * @param array $columns
     * @param bool $queryChildren
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function descendants(array $columns = ['*'], $queryChildren = false)
    {
        $depthOperator = '>';
        $depthValue = 0;

        if ($queryChildren === true)
        {
            $depthOperator = '=';
            $depthValue = 1;
        }

        return $this->select($columns)
            ->join($this->qattrs['closure'], $this->qattrs['descendant'], '=', $this->qattrs['pk'])
            ->where($this->qattrs['ancestor'], '=', $this->qattrs['pkValue'])
            ->where($this->qattrs['depth'], $depthOperator, $depthValue);
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function children(array $columns = ['*'])
    {
        return $this->descendants($columns, true);
    }

    /**
     * @param $position
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function childAt($position, array $columns = ['*'])
    {
        return $this->children($columns)->where(EntityInterface::POSITION, '=', $position);
    }

    /**
     * @param string $find
     * @param string $direction
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    protected function getSiblingsQuery($find = 'all', $direction = 'both', array $columns = ['*'])
    {
        $query = $this->select($columns)
            ->join($this->qattrs['closure'].' as c', 'c.'.$this->qattrs['descendantShort'], '=', $this->qattrs['pk'])
            ->where($this->qattrs['depthShort'], '=', $this->qattrs['depthValue'])
            ->where($this->qattrs['ancestorShort'], '=', $this->qattrs['ancestorValue']);

        if ($find == 'all' && $direction == 'both')
        {
            $query->where($this->qattrs['descendantShort'], '<>', $this->qattrs['pkValue']);
        }
        else if ($find == 'one' && $direction == 'both')
        {
            $position = [
                $this->qattrs['positionValue']-1,
                $this->qattrs['positionValue']+1
            ];

            $query->whereIn($this->qattrs['position'], $position);
        }
        else
        {
            switch($direction)
            {
                case 'prev':
                    $operand = '<';
                    $position = $this->qattrs['positionValue']-1;
                    break;

                case 'next':
                    $operand = '>';
                    $position = $this->qattrs['positionValue']+1;
                    break;
            }

            $operand = ($find == 'all' ? $operand : '=');

            if ($find == 'all')
            {
                $position = $this->qattrs['positionValue'];
            }

            $query->where($this->qattrs['position'], $operand, $position);
        }

        if ($this->qattrs['depthValue'] == 0)
        {
            $query->whereRaw($this->getRootCheckQuery());
        }

        return $query;
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function siblings(array $columns = ['*'])
    {
        return $this->getSiblingsQuery('all', 'both', $columns);
    }

    public function siblingAt($position, array $columns = ['*'])
    {
        return $this->siblings($columns)->where(EntityInterface::POSITION, '=', $position);
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function neighbors(array $columns = ['*'])
    {
        return $this->getSiblingsQuery('one', 'both', $columns);
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function prevSiblings(array $columns = ['*'])
    {
        return $this->getSiblingsQuery('all', 'prev', $columns);
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function prevSibling(array $columns = ['*'])
    {
        return $this->getSiblingsQuery('one', 'prev', $columns);
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function nextSiblings(array $columns = ['*'])
    {
        return $this->getSiblingsQuery('all', 'next', $columns);
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function nextSibling(array $columns = ['*'])
    {
        return $this->getSiblingsQuery('one', 'next', $columns);
    }

    /**
     * @param string $closureTableAlias
     * @return string
     */
    protected function getRootCheckQuery($closureTableAlias = 'c')
    {
        return '(select count(*) from '.$this->qattrs['closure'].' as ct '.
               'where ct.'.$this->qattrs['descendantShort'].' = '.$closureTableAlias.'.'.$this->qattrs['ancestorShort'].' '.
               'and ct.'.$this->qattrs['depthShort'].' > 0) = 0';
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function roots(array $columns = ['*'])
    {
        array_push($columns, 'c.'.$this->qattrs['ancestorShort']);

        return $this->select($columns)
            ->distinct()
            ->join($this->qattrs['closure'].' as c', function($join)
                {
                    $join->on('c.'.$this->qattrs['ancestorShort'], '=', $this->qattrs['pk']);
                    $join->on('c.'.$this->qattrs['descendantShort'], '=', $this->qattrs['pk']);
                })
            ->whereRaw($this->getRootCheckQuery());
    }

    /**
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function tree(array $columns = ['*'])
    {
        $whereIn = function(QueryBuilder $q){
            return $q->select($this->qattrs['ancestor'])
                ->from($q->qattrs['closure'])
                ->whereRaw($q->getRootCheckQuery($q->qattrs['closure']));
        };

        $query = $this->select($columns)
            ->join($this->qattrs['closure'], $this->qattrs['descendant'], '=', $this->qattrs['pk'])
            ->whereIn($this->qattrs['ancestor'], $whereIn);

        return $query;
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