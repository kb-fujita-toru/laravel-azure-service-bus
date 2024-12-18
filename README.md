# Laravel Azure Service Bus Queue Driver

## Installation

### 1. Install the package via Composer:

```bash
composer require hayrican/laravel-azure-service-bus
```

### 3. Configure `.env` file:

In your `.env` file, set the Azure Service Bus connection details:

```env

AZURE_SERVICE_BUS_NAMESPACE=<your-namespace>
AZURE_SERVICE_BUS_QUEUE=<your-queue-name>
AZURE_SERVICE_KEY_NAME=<your-key-name>
AZURE_SERVICE_KEY_VALUE=<your-key>

```

### **4. Define Azure Service Bus Configuration**

Update your `config/queue.php` file to include the following configuration for the Azure Service Bus driver:

```php
'connections' => [
    // Other connections...

    'azure-service-bus' => [
        'driver' => 'azure-service-bus',
        'namespace' => env('AZURE_SERVICE_BUS_NAMESPACE'),
        'queue' => env('AZURE_SERVICE_BUS_QUEUE'),
        'key_name' => env('AZURE_SERVICE_KEY_NAME'),
        'key' => env('AZURE_SERVICE_KEY_VALUE'),
    ]
],
```

---

## License

This project is licensed under the MIT License - see the [License File](LICENSE) for details

---

## Author

[Hayri Can BARÇIN]  
Email: [Contact Me]

[Hayri Can BARÇIN]: <https://www.linkedin.com/in/hayricanbarcin/>
[Contact Me]: <mailto:hayricanbarcin@gmail.com>
