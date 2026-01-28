<?php
declare(strict_types=1);

namespace App;

use Franzose\ClosureTable\ClosureTable;
use Franzose\ClosureTable\Entity;

class Node extends Entity
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nodes';

    /**
     * ClosureTable model instance.
     */
    protected string|ClosureTable $closure = NodeClosure::class;
}
