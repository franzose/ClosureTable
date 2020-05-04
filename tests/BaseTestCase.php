<?php
namespace Franzose\ClosureTable\Tests;

use DB;
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

    public function tearDown()
    {
        // this is to avoid "too many connection" errors
        DB::disconnect(static::DATABASE_CONNECTION);
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

    public static function assertModelAttribute($attribute, array $expected)
    {
        $actual = Entity::whereIn('id', array_keys($expected))
            ->get(['id', $attribute])
            ->pluck($attribute, 'id')
            ->toArray();

        static::assertEquals($expected, $actual);
    }
}
