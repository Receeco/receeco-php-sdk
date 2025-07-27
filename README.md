# ReCeeco PHP SDK

Official PHP client for the [ReCeeco](https://receeco.com) digital receipt API.

## Installation

Install via Composer:

```bash
composer require receeco/php-sdk
```

## Requirements

- PHP 7.4 or higher
- ext-json
- ext-curl
- GuzzleHttp 7.0+

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use Receeco\Client;
use Receeco\Exceptions\SDKError;

$client = new Client([
    'api_key' => 'your-api-key-here', // or 'test-key' for testing
]);

try {
    // Create a receipt
    $receipt = $client->createReceipt([
        'merchant_string_id' => 'test-grocery-store-001',
        'merchant_name' => 'Green Grocery Store',
        'items' => [
            [
                'name' => 'Rice (5 kg)',
                'quantity' => 1,
                'unit_price' => 8500,
                'total_price' => 8500,
            ]
        ],
        'total_amount' => 8500,
        'currency' => 'NGN',
        'payment_method' => 'card',
        'category' => 'Grocery',
    ]);

    echo "Receipt created: " . $receipt['token'] . "\n";
    echo "Receipt URL: https://receeco.com/receipt/" . $receipt['token'] . "\n";

} catch (SDKError $e) {
    echo "Error: " . $e->getErrorCode() . " - " . $e->getMessage() . "\n";
}
?>
```

## API Reference

### `Client::__construct(array $options)`

Initialize the ReCeeco client.

**Parameters:**
- `$options` (array): Configuration options
  - `api_key` (string): Your ReCeeco API key
  - `base_url` (string, optional): API base URL (defaults to production)

### `createReceipt(array $data): array`

Create a digital receipt from transaction data.

**Required fields:**
- `merchant_string_id` (string): Your merchant identifier
- `items` (array): Array of items with `name`, `quantity`, `unit_price`, `total_price`
- `total_amount` (int): Total transaction amount in kobo/cents
- `category` (string): Receipt category (e.g., "Grocery", "Restaurant", "Retail")

**Optional fields:**
- `merchant_name` (string): Business name for display
- `merchant_logo` (string): URL to business logo
- `accent_color` (string): Brand color (hex format)
- `customer_email` (string): Customer email for notifications
- `customer_phone` (string): Customer phone for SMS notifications
- `currency` (string): Currency code (defaults to "NGN")
- `payment_method` (string): Payment method used
- `location` (string): Store location
- `transaction_date` (string): ISO date string (auto-generated if not provided)

**Returns:** Array with `id` and `token`

### `getReceipt(string $tokenOrCode): array`

Fetch an existing receipt using token or 6-digit short code.

**Parameters:**
- `$tokenOrCode` (string): Receipt token or short code

**Returns:** Complete receipt data as array

### `updateReceiptContact(array $data): array`

Add or update customer contact information on a receipt.

**Parameters:**
- `$data` (array): Update data
  - `token` (string): Receipt token or short code
  - `email` (string, optional): Customer email
  - `phone` (string, optional): Customer phone

**Returns:** Array with `success` status

## Error Handling

```php
<?php

use Receeco\Client;
use Receeco\Exceptions\SDKError;

$client = new Client(['api_key' => 'test-key']);

try {
    $receipt = $client->getReceipt('INVALID');
} catch (SDKError $e) {
    echo "Error Code: " . $e->getErrorCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
}
?>
```

## Examples

### Basic Receipt Creation

```php
<?php

require_once 'vendor/autoload.php';

use Receeco\Client;

$client = new Client(['api_key' => 'test-key']);

$receipt = $client->createReceipt([
    'merchant_string_id' => 'test-coffee-shop',
    'merchant_name' => 'Demo Coffee Shop',
    'items' => [
        [
            'name' => 'Cappuccino',
            'quantity' => 2,
            'unit_price' => 500,
            'total_price' => 1000,
        ],
        [
            'name' => 'Croissant',
            'quantity' => 1,
            'unit_price' => 300,
            'total_price' => 300,
        ]
    ],
    'total_amount' => 1300,
    'category' => 'Restaurant',
    'payment_method' => 'card',
    'customer_email' => 'customer@example.com',
]);

echo "Receipt created with token: " . $receipt['token'] . "\n";
?>
```

### Laravel Integration

```php
<?php

// In your Laravel controller
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Receeco\Client;
use Receeco\Exceptions\SDKError;

class ReceiptController extends Controller
{
    private Client $receecoClient;

    public function __construct()
    {
        $this->receecoClient = new Client([
            'api_key' => config('services.receeco.api_key')
        ]);
    }

    public function createReceipt(Request $request): JsonResponse
    {
        try {
            $receipt = $this->receecoClient->createReceipt([
                'merchant_string_id' => 'your-store-id',
                'merchant_name' => 'Your Store Name',
                'items' => $request->input('items'),
                'total_amount' => (int) $request->input('total'),
                'category' => 'Retail',
                'customer_email' => $request->input('email'),
            ]);

            return response()->json([
                'success' => true,
                'receipt_url' => 'https://receeco.com/receipt/' . $receipt['token']
            ]);

        } catch (SDKError $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
?>
```

### WordPress Plugin Integration

```php
<?php

// In your WordPress plugin
add_action('wp_ajax_create_receipt', 'handle_create_receipt');
add_action('wp_ajax_nopriv_create_receipt', 'handle_create_receipt');

function handle_create_receipt() {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    
    use Receeco\Client;
    use Receeco\Exceptions\SDKError;

    $client = new Client([
        'api_key' => get_option('receeco_api_key')
    ]);

    try {
        $receipt = $client->createReceipt([
            'merchant_string_id' => get_option('receeco_merchant_id'),
            'merchant_name' => get_bloginfo('name'),
            'items' => json_decode(stripslashes($_POST['items']), true),
            'total_amount' => (int) $_POST['total'],
            'category' => $_POST['category'] ?? 'Retail',
            'customer_email' => $_POST['email'] ?? null,
        ]);

        wp_send_json_success([
            'receipt_token' => $receipt['token'],
            'receipt_url' => 'https://receeco.com/receipt/' . $receipt['token']
        ]);

    } catch (SDKError $e) {
        wp_send_json_error([
            'message' => $e->getMessage(),
            'code' => $e->getErrorCode()
        ]);
    }
}
?>
```

### Standalone PHP Script

```php
<?php

require_once 'vendor/autoload.php';

use Receeco\Client;
use Receeco\Exceptions\SDKError;

function createSampleReceipt() {
    $client = new Client(['api_key' => 'test-key']);

    try {
        echo "🧾 Creating a test receipt...\n";
        
        $receipt = $client->createReceipt([
            'merchant_string_id' => 'test-php-demo-store',
            'merchant_name' => 'PHP Demo Store',
            'merchant_logo' => 'https://example.com/logo.png',
            'accent_color' => '#8B4513',
            'customer_email' => 'customer@example.com',
            'items' => [
                [
                    'name' => 'Product A',
                    'quantity' => 2,
                    'unit_price' => 1500,
                    'total_price' => 3000,
                ],
                [
                    'name' => 'Product B',
                    'quantity' => 1,
                    'unit_price' => 2500,
                    'total_price' => 2500,
                ]
            ],
            'total_amount' => 5500,
            'currency' => 'NGN',
            'payment_method' => 'cash',
            'category' => 'Retail',
            'location' => 'Lagos, Nigeria'
        ]);

        echo "✅ Receipt created successfully!\n";
        echo "📄 Receipt ID: " . $receipt['id'] . "\n";
        echo "🔗 Receipt Token: " . $receipt['token'] . "\n";
        echo "🌐 Receipt URL: https://receeco.com/receipt/" . $receipt['token'] . "\n";

        // Fetch the receipt back
        echo "\n📥 Fetching receipt...\n";
        $fetchedReceipt = $client->getReceipt($receipt['token']);
        echo "✅ Fetched receipt for: " . ($fetchedReceipt['merchant_name'] ?? 'Unknown') . "\n";
        echo "💰 Total Amount: ₦" . $fetchedReceipt['total_amount'] . "\n";

        // Update contact info
        echo "\n📞 Updating customer contact...\n";
        $updateResult = $client->updateReceiptContact([
            'token' => $receipt['token'],
            'phone' => '+2348123456789'
        ]);
        echo "✅ Contact updated: " . ($updateResult['success'] ? 'Yes' : 'No') . "\n";

    } catch (SDKError $e) {
        echo "❌ Error [" . $e->getErrorCode() . "]: " . $e->getMessage() . "\n";
    }
}

createSampleReceipt();
?>
```

## Testing

For testing, use merchant IDs starting with "test-" (e.g., "test-my-store"). Test merchants are automatically created.

```php
<?php

$client = new Client(['api_key' => 'test-key']);

$receipt = $client->createReceipt([
    'merchant_string_id' => 'test-demo-store',
    'merchant_name' => 'Test Demo Store',
    'items' => [
        [
            'name' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000
        ]
    ],
    'total_amount' => 1000,
    'category' => 'Test',
]);
?>
```

## Development

Run tests:
```bash
composer test
```

Check code style:
```bash
composer cs-check
```

Fix code style:
```bash
composer cs-fix
```

Static analysis:
```bash
composer analyze
```

## License

MIT © ReCeeco 