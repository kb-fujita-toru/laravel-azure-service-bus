<?php

namespace HayriCan\LaravelAzureServiceBus\Drivers;

use Exception;
use HayriCan\LaravelAzureServiceBus\Connectors\AzureServiceBusConnection;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\Jobs\Job;

class AzureServiceBusJob extends Job implements JobContract
{
    /**
     * The Azure Service Bus connection instance.
     *
     * @var AzureServiceBusConnection
     */
    protected AzureServiceBusConnection $connection;

    /**
     * The Azure Service Bus job instance.
     *
     * @var array
     */
    protected array $job;

    /**
     * Create a new job instance.
     *
     * @param Container $container
     * @param AzureServiceBusConnection $connection
     * @param array $job
     * @param $connectionName
     * @param $queue
     */
    public function __construct(Container $container, AzureServiceBusConnection $connection, array $job, $connectionName, $queue)
    {
        $this->connection = $connection;
        $this->job = $job;
        $this->queue = $queue;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     * @return void
     * @throws Exception
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $response = Http::withHeaders([
            'Authorization' => $this->connection->generateSasToken()
        ])->put($this->getLocation() . '?timeout=' . $delay);

        if (!$response->successful()) {
            throw new Exception("Failed to unlock message: {$response->body()}");
        }
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     * @throws Exception
     */
    public function delete()
    {
        parent::delete();

        $response = Http::withHeaders([
            'Authorization' => $this->connection->generateSasToken()
        ])->delete($this->getLocation());

        if (!$response->successful()) {
            throw new Exception("Failed to delete message: {$response->body()}");
        }
    }

    /**
     * Get the number of attempts for the job.
     *
     * @return int
     */
    public function attempts(): int
    {
        return $this->job['DeliveryCount'];
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return $this->job['MessageId'];
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->job['Body'];
    }

    /**
     * Get the Azure Service Bus job location.
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->job['Location'];
    }
}
