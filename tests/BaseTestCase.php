<?php
namespace Franzose\ClosureTable\Tests;

use Dotenv\Dotenv;
use Franzose\ClosureTable\Contracts\ClosureTableInterface;
use Franzose\ClosureTable\Contracts\EntityInterface;
use Franzose\ClosureTable\Models\ClosureTable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;
use Franzose\ClosureTable\Models\Entity;

abstract class BaseTestCase extends TestCase
{
    const DATABASE_CONNECTION = 'closuretable';

    public function setUp()
    {
        parent::setUp();

        $this->app->setBasePath(__DIR__ . '/../');
        $this->app->bind(EntityInterface::class, Entity::class);
        $this->app->bind(ClosureTableInterface::class, ClosureTable::class);

        $artisan = $this->app->make(Kernel::class);

        $artisan->call('migrate:refresh', [
            '--database' => static::DATABASE_CONNECTION,
            '--path' => 'tests/migrations',
            '--seeder' => EntitiesSeeder::class
        ]);
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $envFilePath = __DIR__ . '/..';

        if (file_exists($envFilePath . '/.env.testing')) {
            (new Dotenv($envFilePath, '.env.testing'))->load();
        }

        $app['config']->set('database.default', static::DATABASE_CONNECTION);
        $app['config']->set('database.connections.' . static::DATABASE_CONNECTION, [
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT'),
            'database' => env('DB_NAME', static::DATABASE_CONNECTION . 'test'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD'),
            'prefix' => '',
            'charset' => 'utf8',
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
        ]);
    }

    /**
     * Asserts if two arrays have similar values, sorting them before the fact in order to "ignore" ordering.
     * @param array $actual
     * @param array $expected
     * @param string $message
     * @param float $delta
     * @param int $depth
     */
    protected function assertArrayValuesEquals(array $actual, array $expected, $message = '', $delta = 0.0, $depth = 10)
    {
        $this->assertEquals($actual, $expected, $message, $delta, $depth, true);
    }

    public static function assertPositions(array $expectedPositions, array $entityIds)
    {
        $actualPositions = Entity::whereIn('id', $entityIds)
            ->get(['position'])
            ->pluck('position')
            ->toArray();

        static::assertEquals($expectedPositions, $actualPositions);
    }
}
