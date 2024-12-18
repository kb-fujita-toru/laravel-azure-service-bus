<?php

namespace HayriCan\LaravelAzureServiceBus\Drivers;

use Exception;
use HayriCan\LaravelAzureServiceBus\Connectors\AzureServiceBusConnection;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AzureServiceBusQueue extends Queue implements QueueContract, ClearableQueue
{
    protected AzureServiceBusConnection $connection;

    /**
     * Create a new AzureServiceBusQueue instance.
     *
     * @param AzureServiceBusConnection $connection
     *
     */
    public function __construct(AzureServiceBusConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Push a job onto the queue.
     *
     * @param object|string $job
     * @param string $data
     * @param null $queue
     * @throws Exception
     */
    public function push($job, $data = '', $queue = null): void
    {
        $this->pushRaw($this->createPayload($job, $queue, $data));
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param null $queue
     * @param array $options
     * @throws Exception
     */
    public function pushRaw($payload, $queue = null, array $options = []): void
    {
        $url = $this->connection->uri . 'messages';
        $token = $this->connection->generateSasToken();

        $header = [
            'Authorization' => $token,
            'Accept' => 'application/json',
        ];
        if (!empty($options)) {
            $header = array_merge($header, $options);
        }

        $response = Http::withHeaders($header)
        ->post($url, json_decode($payload, true));

        if ($response->status() != Response::HTTP_CREATED) {
            throw new Exception('Failed to push message:' . $response->body());
        }
    }

    /**
     * Push a job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param mixed $job The job instance or job class name.
     * @param mixed $data Additional data for the job (optional).
     * @param string|null $queue The queue name (optional).
     * @return void
     * @throws Exception
     */
    public function later($delay, $job, $data = '', $queue = null): void
    {
        $options = ['BrokerProperties' => json_encode([
            'ScheduledEnqueueTimeUtc' => now()->addSeconds($this->secondsUntil($delay))->toIso8601String(),
        ])];
        $this->pushRaw($this->createPayload($job, $queue, $data), options: $options);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param null $queue
     * @return AzureServiceBusJob|null
     */
    public function pop($queue = null): ?AzureServiceBusJob
    {
        $url = $this->connection->uri . 'messages/head';
        $token = $this->connection->generateSasToken();

        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/atom+xml;type=entry;charset=utf-8',
        ])
        ->post($url);

        if ($response->successful()) {
            if (array_key_exists('BrokerProperties', $response->headers())) {
                $message = json_decode($response->headers()['BrokerProperties'][0], true);
                $message['Location'] = $response->headers()['Location'][0];
                $message['Body'] = $response->body();

                return new AzureServiceBusJob(
                    $this->container,
                    $this->connection,
                    $message,
                    $this->connectionName,
                    $queue
                );
            }
        }

        return null;
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size($queue = null): int
    {
        $url = $this->connection->uri;
        $token = $this->connection->generateSasToken();

        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/atom+xml; type=entry',
        ])
            ->get($url);

        if ($response->successful()) {
            $xmlResponse = simplexml_load_string($response->body(), "SimpleXMLElement", LIBXML_NOCDATA);
            $attributes = json_decode(json_encode($xmlResponse), true);

            return (int) $attributes['content']['QueueDescription']['MessageCount'];
        }

        return 0;
    }

    /**
     * Delete all the jobs from the queue.
     *
     * @param string $queue
     * @return int
     * @throws Exception
     */
    public function clear($queue): int
    {
        $messageCount = $this->size($queue);
        return tap($messageCount, function () use ($queue, $messageCount) {
            $url = $this->connection->uri . 'messages/head';
            $token = $this->connection->generateSasToken();

            for ($i = 0; $i < $messageCount; $i++) {
                $response = Http::withHeaders([
                    'Authorization' => $token,
                    'Content-Type' => 'application/atom+xml;type=entry;charset=utf-8',
                ])
                    ->delete($url);

                if (!$response->successful()) {
                    throw new Exception("Queue could not be cleared: HTTP {$response->status()}. Response: {$response->body()}");
                }
            }
        });
    }
}
