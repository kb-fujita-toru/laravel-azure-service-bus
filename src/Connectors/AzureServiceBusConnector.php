<?php

namespace HayriCan\LaravelAzureServiceBus\Connectors;

use HayriCan\LaravelAzureServiceBus\Drivers\AzureServiceBusQueue;
use Illuminate\Queue\Connectors\ConnectorInterface;

class AzureServiceBusConnector implements ConnectorInterface
{
    public function connect(array $config): AzureServiceBusQueue
    {
        $connection = new AzureServiceBusConnection(
            $config['namespace'],
            $config['queue'],
            $config['key_name'],
            $config['key']
        );

        return new AzureServiceBusQueue($connection);
    }
}
