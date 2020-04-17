<?php
namespace Foo;

use Franzose\ClosureTable\Models\Entity;

class Bar extends Entity
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bars';

    /**
     * ClosureTable model instance.
     *
     * @var \Foo\BarTree
     */
    protected $closure = 'Foo\BarTree';
}
