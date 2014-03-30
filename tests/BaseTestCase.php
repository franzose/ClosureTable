<?php namespace Franzose\ClosureTable\Tests;

use \Orchestra\Testbench\TestCase;
use \Mockery;

/**
 * Class BaseTestCase
 * @package Franzose\ClosureTable\Tests
 */
abstract class BaseTestCase extends TestCase {
    use \Way\Tests\ModelHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->app->bind('Franzose\ClosureTable\Contracts\EntityInterface', 'Franzose\ClosureTable\Models\Entity');
        $this->app->bind('Franzose\ClosureTable\Contracts\ClosureTableInterface', 'Franzose\ClosureTable\Models\ClosureTable');

        $artisan = $this->app->make('artisan');
        $artisan->call('migrate', [
            '--database' => 'closuretable',
            '--path' => '../tests/migrations'
        ]);

        $artisan->call('db:seed', [
            '--class' => 'Franzose\ClosureTable\Tests\Seeds\EntitiesSeeder'
        ]);
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
            'database' => ':memory:',
            'prefix'   => '',
        ));
    }
} 