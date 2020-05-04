<?php
namespace Foo;

use Franzose\ClosureTable\Models\Entity;

class Foo extends Entity
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'foo';

    /**
     * ClosureTable model instance.
     *
     * @var \Foo\FooTree
     */
    protected $closure = 'Foo\FooTree';
}
