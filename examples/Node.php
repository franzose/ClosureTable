<?php

namespace App;

use Franzose\ClosureTable\Models\Entity;

class Node extends Entity
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nodes';

    /**
     * ClosureTable model instance.
     *
     * @var string
     */
    protected $closure = NodeClosure::class;
}
