<?php
namespace Foo;

use Franzose\ClosureTable\Models\Entity;

class FooBar extends Entity
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'foo_bar';

    /**
     * ClosureTable model instance.
     *
     * @var \Foo\FooBarClosure
     */
    protected $closure = 'Foo\FooBarClosure';
}
