<?php
namespace Franzose\ClosureTable\Models;

use Franzose\ClosureTable\Traits\ClosureTableTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Franzose\ClosureTable\Contracts\ClosureTableInterface;

/**
 * Basic ClosureTable model. Performs actions on the relationships table.
 *
 * @package Franzose\ClosureTable
 */
class ClosureTable extends Eloquent implements ClosureTableInterface
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'entities_closure';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'closure_id';

    use ClosureTableTrait;
}
