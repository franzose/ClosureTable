<?php
declare(strict_types=1);

namespace App;

use Franzose\ClosureTable\ClosureTable;
use Franzose\ClosureTable\Entity;

class Node extends Entity
{
    // make these fields fillable for the sake of examples
    protected $fillable = [
        'id',
        'parent_id',
    ];

    protected $table = 'nodes';

    /**
     * ClosureTable model instance.
     */
    protected string|ClosureTable $closure = NodeClosure::class;
}
