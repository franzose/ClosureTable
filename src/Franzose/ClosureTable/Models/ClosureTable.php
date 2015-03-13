<?php
namespace Franzose\ClosureTable\Models;

use Franzose\ClosureTable\Contracts\ClosureTableInterface;
use Franzose\ClosureTable\Traits\ClosureTableTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Basic ClosureTable model. Performs actions on the relationships table.
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
