<?php

namespace HayriCan\LaravelAzureServiceBus\Connectors;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AzureServiceBusConnection
{
    public string $queue;
    public string $namespace;
    public string $uri;
    public string $version;

    private string $keyName;
    private string $keyValue;

    const AZURE_SERVICE_BUS_SAS_TOKEN = 'azure:service-bus-sas-token-%s';

    /**
     * Create a new AzureServiceBusConnection instance.
     * @param string $namespace
     * @param string $queue
     * @param string $keyName
     * @param string $keyValue
     * @return void
     */
    public function __construct(string $namespace, string $queue, string $keyName, string $keyValue)
    {
        $this->namespace = $namespace;
        $this->queue = $queue;
        $this->keyName = $keyName;
        $this->keyValue = $keyValue;
        $this->uri = sprintf('https://%s.servicebus.windows.net/%s/', $this->namespace,$this->queue);
    }

    /**
     * Generate Shared Access Signatures(SAS) Token
     *
     * @return string
     */
    public function generateSasToken(): string
    {
        $time = 60 * 60 * 24 * 7; // 7 days
        $cacheKey = sprintf(self::AZURE_SERVICE_BUS_SAS_TOKEN, $this->queue);

        return Cache::remember(
            $cacheKey,
            Carbon::now()->addSeconds($time - 3600),
            function () use ($time) {
                $targetUri = strtolower(rawurlencode(strtolower($this->uri)));
                $expires = time() + $time;
                $toSign = $targetUri . "\n" . $expires;
                $signature = rawurlencode(base64_encode(hash_hmac(
                    'sha256',
                    $toSign,
                    $this->keyValue,
                    true
                )));

                return "SharedAccessSignature sr=" . $targetUri .
                    "&sig=" . $signature .
                    "&se=" . $expires .
                    "&skn=" . $this->keyName;
            }
        );
    }
}
