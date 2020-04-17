<?php

namespace Franzose\ClosureTable\Tests\Console;

use Carbon\Carbon;
use Franzose\ClosureTable\ClosureTableServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bootstrap\LoadConfiguration as LaravelLoadConfiguration;
use Illuminate\Support\Composer;
use Orchestra\Testbench\Bootstrap\LoadConfiguration as TestbenchLoadConfiguration;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;

final class MakeCommandTests extends TestCase
{
    /**
     * @var Kernel
     */
    private $artisan;
    private $modelsPath;
    private $migrationsPath;

    protected function resolveApplication()
    {
        return tap(new CustomApplication($this->getBasePath()), static function ($app) {
            $app->bind(
                LaravelLoadConfiguration::class,
                TestbenchLoadConfiguration::class
            );
        });
    }

    public function setUp()
    {
        parent::setUp();

        $this->app->setBasePath(__DIR__);
        $this->app->register(new ClosureTableServiceProvider($this->app));
        $this->app->bind(Composer::class, static function () {
            return new ComposerStub();
        });

        $this->artisan = $this->app->make(Kernel::class);
        $this->modelsPath = $this->app->path();
        $this->migrationsPath = $this->app->databasePath('migrations');
    }

    public function testCommandMustRequireEntityName()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "entity").');

        $this->artisan->call('closuretable:make');
    }

    public function testCommandShouldGenerateFilesAtDefaultPaths()
    {
        Carbon::setTestNow('2020-04-17 00:00:00');

        $this->artisan->call('closuretable:make', [
            'entity' => 'Foo',
            '--namespace' => 'Foo',
            '--entity-table' => 'foo',
            '--closure' => 'FooTree',
            '--closure-table' => 'foo_tree',
            '--use-innodb' => true
        ]);

        Carbon::setTestNow();

        $actualFoo = $this->modelsPath . '/Foo.php';
        $actualFooClosure = $this->modelsPath . '/FooTree.php';
        $expectedFoo = $this->modelsPath . '/expectedFoo.php';
        $expectedFooClosure = $this->modelsPath . '/expectedFooTree.php';
        $expectedMigrationPath = $this->migrationsPath . '/expectedFooMigration.php';
        $actualMigrationPath = $this->migrationsPath . '/2020_04_17_000000_create_foos_table_migration.php';

        static::assertFileExists($actualFoo);
        static::assertFileExists($actualFooClosure);
        static::assertFileEquals($expectedFoo, $actualFoo);
        static::assertFileEquals($expectedFooClosure, $actualFooClosure);
        static::assertFileExists($actualMigrationPath);
        static::assertFileEquals($expectedMigrationPath, $actualMigrationPath);

        unlink($actualFoo);
        unlink($actualFooClosure);
        unlink($actualMigrationPath);
    }

    public function testCommandShouldGenerateFilesAtCustomPaths()
    {
        $filesystem = new Filesystem();

        $customModelsPath = $this->modelsPath . '/custom';
        $customMigrationsPath = $this->migrationsPath . '/custom';

        if (!$filesystem->exists($customModelsPath)) {
            $filesystem->makeDirectory($customModelsPath);
        }

        if (!$filesystem->exists($customMigrationsPath)) {
            $filesystem->makeDirectory($customMigrationsPath);
        }

        Carbon::setTestNow('2020-04-17 00:00:00');

        $this->artisan->call('closuretable:make', [
            'entity' => 'Foo',
            '--namespace' => 'Foo',
            '--entity-table' => 'foo',
            '--closure' => 'FooTree',
            '--closure-table' => 'foo_tree',
            '--models-path' => $customModelsPath,
            '--migrations-path' => $customMigrationsPath
        ]);

        Carbon::setTestNow();

        $models = $filesystem->files($customModelsPath);
        $migrations = $filesystem->files($customMigrationsPath);

        static::assertCount(2, $models);
        static::assertCount(1, $migrations);

        $filesystem->deleteDirectory($customModelsPath);
        $filesystem->deleteDirectory($customMigrationsPath);
    }

    public function testCommandShouldHandleNamespacedModelNames()
    {
        Carbon::setTestNow('2020-04-17 00:00:00');

        $this->artisan->call('closuretable:make', [
            'entity' => 'Foo\\Bar',
            '--closure' => 'Foo\\BarTree',
        ]);

        Carbon::setTestNow();

        $actualFoo = $this->modelsPath . '/Bar.php';
        $actualFooClosure = $this->modelsPath . '/BarTree.php';
        $expectedFoo = $this->modelsPath . '/expectedBar.php';
        $expectedFooClosure = $this->modelsPath . '/expectedBarTree.php';
        $expectedMigrationPath = $this->migrationsPath . '/expectedBarMigration.php';
        $actualMigrationPath = $this->migrationsPath . '/2020_04_17_000000_create_bars_table_migration.php';

        static::assertFileExists($actualFoo);
        static::assertFileExists($actualFooClosure);
        static::assertFileEquals($expectedFoo, $actualFoo);
        static::assertFileEquals($expectedFooClosure, $actualFooClosure);
        static::assertFileExists($actualMigrationPath);
        static::assertFileEquals($expectedMigrationPath, $actualMigrationPath);

        unlink($actualFoo);
        unlink($actualFooClosure);
        unlink($actualMigrationPath);
    }
}
