<?php

namespace AdFinder\Providers;

use Illuminate\Support\ServiceProvider;
use AdFinder\Helpers\DuplitronMatcher;

class MatcherServiceProvider extends ServiceProvider
{

    // Set it so this class will only be loaded when necessary
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('AdFinder\Helpers\Contracts\MatcherContract', function(){
            return new DuplitronMatcher();
        });
    }

     /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['AdFinder\Helpers\Contracts\MatcherContract'];
    }
}
