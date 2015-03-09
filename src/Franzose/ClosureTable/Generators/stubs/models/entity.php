<?php
{{namespace}}

use Franzose\ClosureTable\Models\Entity;

class {{entity_class}} extends Entity implements {{entity_class}}Interface
{
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
}
