<?php

namespace Franzose\ClosureTable\Tests\Generators;

use Carbon\Carbon;
use Franzose\ClosureTable\Generators\Migration;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class MigrationTests extends TestCase
{
    /**
     * @dataProvider useInnoDbDataProvider
     */
    public function testGenerator($useInnoDb)
    {
        $generator = new Migration(new Filesystem());

        Carbon::setTestNow('2020-04-03 00:00:00');

        $generator->create([
            'migrations-path' => __DIR__,
            'entity-table' => 'entity',
            'closure-table' => 'entity_tree',
            'use-innodb' => $useInnoDb
        ]);

        Carbon::setTestNow();

        $entityMigrationPath = __DIR__ . '/2020_04_03_000000_create_entities_table.php';
        $closureMigrationPath = __DIR__ . '/2020_04_03_000000_create_entity_trees_table.php';

        static::assertFileExists($entityMigrationPath);
        static::assertFileExists($closureMigrationPath);

        $expectedEntityMigrationPath = sprintf(
            '%s/expectedEntity%sMigration.php',
            __DIR__,
            $useInnoDb ? 'InnoDb' : ''
        );

        $expectedClosureMigrationPath = sprintf(
            '%s/expectedClosure%sMigration.php',
            __DIR__,
            $useInnoDb ? 'InnoDb' : ''
        );

        static::assertFileEquals($expectedEntityMigrationPath, $entityMigrationPath);
        static::assertFileEquals($expectedClosureMigrationPath, $closureMigrationPath);

        unlink($entityMigrationPath);
        unlink($closureMigrationPath);
    }

    public function useInnoDbDataProvider()
    {
        return [
            [true, false]
        ];
    }
}
