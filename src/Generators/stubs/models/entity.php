<?php
{{namespace}}

use Franzose\ClosureTable\Models\Entity;

class {{entity_class}} extends Entity
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
     * @var \{{closure_class}}
     */
    protected $closure = '{{closure_class}}';
}
