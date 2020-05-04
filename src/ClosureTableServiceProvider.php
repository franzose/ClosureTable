<?php
namespace Franzose\ClosureTable;

use Illuminate\Support\ServiceProvider;
use Franzose\ClosureTable\Console\MakeCommand;

/**
 * ClosureTable service provider
 *
 * @package Franzose\ClosureTable
 */
class ClosureTableServiceProvider extends ServiceProvider
{
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
        $this->app->singleton('command.closuretable.make', static function ($app) {
            return $app[MakeCommand::class];
        });

        $this->commands('command.closuretable.make');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

}
