<?php

namespace Franzose\ClosureTable\Tests\Generators;

use Franzose\ClosureTable\Generators\Model;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ModelTests extends TestCase
{
    public function testGeneration()
    {
        $generator = new Model(new Filesystem());

        $generator->create([
            'namespace' => 'Foo',
            'entity' => 'FooBar',
            'entity-table' => 'foo_bar',
            'closure' => 'FooBarClosure',
            'closure-table' => 'foo_bar_tree',
            'models-path' => __DIR__,
        ]);

        $entityPath = __DIR__ . '/FooBar.php';
        $closurePath = __DIR__ . '/FooBarClosure.php';
        static::assertFileExists($entityPath);
        static::assertFileExists($closurePath);
        static::assertFileEquals(__DIR__ . '/expectedEntity.php', $entityPath);
        static::assertFileEquals(__DIR__ . '/expectedClosure.php', $closurePath);

        unlink($entityPath);
        unlink($closurePath);
    }
}
