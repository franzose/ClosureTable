<?php
namespace Franzose\ClosureTable;

use Illuminate\Support\ServiceProvider;
use Franzose\ClosureTable\Console\ClosureTableCommand;
use Franzose\ClosureTable\Console\MakeCommand;
use Franzose\ClosureTable\Generators\Migration as Migrator;
use Franzose\ClosureTable\Generators\Model as Modeler;

/**
 * ClosureTable service provider
 *
 * @package Franzose\ClosureTable
 */
class ClosureTableServiceProvider extends ServiceProvider
{

    /**
     * Current library version
     */
    const VERSION = 4;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Here we register commands for artisan
        $this->app->singleton('command.closuretable', function ($app) {
            return new ClosureTableCommand;
        });

        $this->app->singleton('command.closuretable.make', function ($app) {
            return $app['Franzose\ClosureTable\Console\MakeCommand'];
        });

        $this->commands('command.closuretable', 'command.closuretable.make');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

}
