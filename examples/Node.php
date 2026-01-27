<?php

namespace App;

use Franzose\ClosureTable\Entity;

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
