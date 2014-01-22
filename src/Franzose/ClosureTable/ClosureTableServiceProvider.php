<?php namespace Franzose\ClosureTable;

use Illuminate\Support\ServiceProvider;

class ClosureTableServiceProvider extends ServiceProvider {

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