<?php

namespace Franzose\ClosureTable\Tests\Extensions;

use Franzose\ClosureTable\Extensions\Str;
use PHPUnit\Framework\TestCase;

final class StrTests extends TestCase
{
    public function testClassify()
    {
        static::assertEquals('FooBar', Str::classify('foo_bars'));
    }

    public function testTableize()
    {
        static::assertEquals('foo_bars', Str::tableize('FooBar'));
        static::assertEquals('foo_qux_bars', Str::tableize('Foo\\Qux\\Bar'));
    }
}
