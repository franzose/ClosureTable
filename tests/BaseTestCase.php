<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests;

use DB;
use Dotenv\Dotenv;
use Franzose\ClosureTable\Entity;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

abstract class BaseTestCase extends TestCase
{
    private const BASE_PATH = __DIR__ . '/../';
    private const ENV_FILE_NAME = '.env.testing';

    protected const DATABASE_CONNECTION = 'closuretable';

    public function setUp(): void
    {
        parent::setUp();

        $this->app->setBasePath(self::BASE_PATH);

        $artisan = $this->app->make(Kernel::class);

        $artisan->call('migrate:refresh', [
            '--database' => static::DATABASE_CONNECTION,
            '--path' => 'tests/migrations',
            '--seeder' => EntitiesSeeder::class
        ]);
    }

    public function tearDown(): void
    {
        // this is to avoid "too many connection" errors
        DB::disconnect(static::DATABASE_CONNECTION);

        parent::tearDown();
    }

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        if (file_exists(self::BASE_PATH . '/' . self::ENV_FILE_NAME)) {
            Dotenv::createImmutable(self::BASE_PATH, self::ENV_FILE_NAME)->load();
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

    public static function assertModelAttribute(string $attribute, array $expected): void
    {
        $actual = Entity::whereIn('id', array_keys($expected))
            ->get(['id', $attribute])
            ->pluck($attribute, 'id')
            ->toArray();

        static::assertEquals($expected, $actual);
    }
}
