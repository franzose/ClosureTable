<?php namespace Franzose\ClosureTable;

use Illuminate\Support\ServiceProvider;
use Franzose\ClosureTable\Console\ClosureTableCommand;
use Franzose\ClosureTable\Console\MakeCommand;
use Franzose\ClosureTable\Generators\Migration as Migrator;
use Franzose\ClosureTable\Generators\Model as Modeler;

class ClosureTableServiceProvider extends ServiceProvider {

    /**
     *
     */
    const VERSION = 3;

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
        $this->package('franzose/ClosureTable');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bind('Franzose\ClosureTable\Contracts\EntityInterface', 'Franzose\ClosureTable\Entity');
        $this->app->bind('Franzose\ClosureTable\Contracts\ClosureTableInterface', 'Franzose\ClosureTable\ClosureTable');
        $this->app->bind('Franzose\ClosureTable\Contracts\EntityRepositoryInterface', 'Franzose\ClosureTable\EntityRepository');

        $this->app['command.closuretable'] = $this->app->share(function($app){
            return new ClosureTableCommand;
        });

        $this->app['command.closuretable.make'] = $this->app->share(function($app){
            $migrator = new Migrator($app['files']);
            $modeler  = new Modeler($app['files']);

            return new MakeCommand($migrator, $modeler);
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