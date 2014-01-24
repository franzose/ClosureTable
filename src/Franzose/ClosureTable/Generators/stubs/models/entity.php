<?php

class {{entity_class}} extends Franzose\ClosureTable\Entity implements {{entity_class}}Interface {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '{{entity_table}}';

    /**
     * Makes closure table from an interface.
     */
    protected function makeClosureTable()
    {
        $this->closure = \App::make('{{closure_interface}}');
    }
}