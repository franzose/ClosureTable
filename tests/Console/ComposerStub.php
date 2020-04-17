<?php

namespace Franzose\ClosureTable\Tests\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

final class ComposerStub extends Composer
{
    public function __construct()
    {
        parent::__construct(new Filesystem());
    }

    public function dumpAutoloads($extra = '')
    {
        // do nothing
    }

    public function dumpOptimized()
    {
        // do nothing
    }

    protected function findComposer()
    {
        return 'composer';
    }
}
