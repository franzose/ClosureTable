<?php
{{namespace}}

use Franzose\ClosureTable\Traits\EntityTrait;
use Illuminate\Database\Eloquent\Model;

class {{entity_class}} extends Model implements {{entity_class}}Interface
{
    use EntityTrait;

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = true;

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
    protected $table = '{{entity_table}}';

    /**
     * ClosureTable model instance.
     *
     * @var {{closure_class_short}}
     */
    protected $closure = '{{closure_class}}';

    public function __construct(array $attributes = [])
    {
        $attributes = $this->initialiseEntityTrait($attributes);

        parent::__construct($attributes);
    }

}
