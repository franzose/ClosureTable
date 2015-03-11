<?php
{{namespace}}

use Franzose\ClosureTable\Traits\ClosureTableTrait;
use Illuminate\Database\Eloquent\Model;

class {{closure_class}} extends Model implements {{closure_class}}Interface
{
    use ClosureTableTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '{{closure_table}}';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'closure_id';
}
