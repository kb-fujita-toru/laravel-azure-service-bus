<?php

namespace HayriCan\LaravelAzureServiceBus;

use HayriCan\LaravelAzureServiceBus\Connectors\AzureServiceBusConnector;
use Illuminate\Support\ServiceProvider;

class AzureServiceBusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $manager = $this->app['queue'];

        $manager->addConnector('azure-service-bus', function () {
            return new AzureServiceBusConnector();
        });
    }
}
