<?php namespace Franzose\ClosureTable\Tests;

use \Orchestra\Testbench\TestCase;
use \Mockery;

use Franzose\ClosureTable\Models\Entity;

/**
 * Class BaseTestCase
 * @package Franzose\ClosureTable\Tests
 */
abstract class BaseTestCase extends TestCase {
    use \Way\Tests\ModelHelpers;

    public static $debug = false;
    public static $sqlite_in_memory = true;

    public function setUp()
    {
        parent::setUp();

        $this->app->bind('Franzose\ClosureTable\Contracts\EntityInterface', 'Franzose\ClosureTable\Models\Entity');
        $this->app->bind('Franzose\ClosureTable\Contracts\ClosureTableInterface', 'Franzose\ClosureTable\Models\ClosureTable');

        if (!static::$sqlite_in_memory)
        {
            \DB::statement('DROP TABLE IF EXISTS entities;');
            \DB::statement('DROP TABLE IF EXISTS entities_closure');
            \DB::statement('DROP TABLE IF EXISTS migrations');
        }

        $artisan = $this->app->make('artisan');
        $artisan->call('migrate', [
            '--database' => 'closuretable',
            '--path' => '../tests/migrations'
        ]);

        $artisan->call('db:seed', [
            '--class' => 'Franzose\ClosureTable\Tests\Seeds\EntitiesSeeder'
        ]);

        if (static::$debug)
        {
            Entity::$debug = true;
            \Event::listen('illuminate.query', function($sql, $bindings, $time){
                $sql = str_replace(array('%', '?'), array('%%', '%s'), $sql);
                $full_sql = vsprintf($sql, $bindings);
                echo PHP_EOL.'- BEGIN QUERY -'.PHP_EOL.$full_sql.PHP_EOL.'- END QUERY -'.PHP_EOL;
            });
        }
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @param \Orchestra\Testbench\Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        $app['path.base'] = __DIR__ . '/../src';

        $app['config']->set('database.default', 'closuretable');
        $app['config']->set('database.connections.closuretable', array(
            'driver'   => 'sqlite',
            'database' => static::$sqlite_in_memory ? ':memory:' : __DIR__.'/../test.sqlite',
            'prefix'   => '',
        ));
    }
} 