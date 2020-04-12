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

        $entityMigrationPath = __DIR__ . '/2020_04_03_000000_create_entities_table_migration.php';

        static::assertFileExists($entityMigrationPath);

        $expectedEntityMigrationPath = sprintf(
            '%s/expectedMigration%s.php',
            __DIR__,
            $useInnoDb ? 'InnoDb' : ''
        );

        static::assertFileEquals($expectedEntityMigrationPath, $entityMigrationPath);

        unlink($entityMigrationPath);
    }

    public function useInnoDbDataProvider()
    {
        return [
            [true, false]
        ];
    }
}
