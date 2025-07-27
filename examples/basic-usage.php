<?php

/**
 * Basic usage example for the ReCeeco PHP SDK.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Receeco\Client;
use Receeco\Exceptions\SDKError;

function main(): void
{
    // Initialize the client
    $client = new Client([
        'api_key' => 'test-key', // Use test key for demo
        'base_url' => 'https://receeco.com/api/trpc' // Optional - this is the default
    ]);

    try {
        echo "🧾 Creating a test receipt...\n";
        
        // Create a receipt
        $receipt = $client->createReceipt([
            'merchant_string_id' => 'test-coffee-shop-php',
            'merchant_name' => 'PHP Demo Coffee Shop',
            'merchant_logo' => 'https://example.com/logo.png',
            'accent_color' => '#8B4513',
            'customer_email' => 'customer@example.com',
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
            'currency' => 'NGN',
            'payment_method' => 'card',
            'category' => 'Restaurant',
            'location' => 'Lagos, Nigeria'
        ]);

        echo "✅ Receipt created successfully!\n";
        echo "📄 Receipt ID: {$receipt['id']}\n";
        echo "🔗 Receipt Token: {$receipt['token']}\n";
        echo "🌐 Receipt URL: https://receeco.com/receipt/{$receipt['token']}\n";

        // Fetch the receipt back
        echo "\n📥 Fetching receipt...\n";
        $fetchedReceipt = $client->getReceipt($receipt['token']);
        $merchantName = $fetchedReceipt['merchant_name'] ?? 'Unknown';
        $customerEmail = $fetchedReceipt['customer_email'] ?? 'Not provided';
        
        echo "✅ Fetched receipt for: {$merchantName}\n";
        echo "💰 Total Amount: ₦{$fetchedReceipt['total_amount']}\n";
        echo "📧 Customer Email: {$customerEmail}\n";

        // Update contact info
        echo "\n📞 Updating customer contact...\n";
        $updateResult = $client->updateReceiptContact([
            'token' => $receipt['token'],
            'phone' => '+2348123456789'
        ]);
        $success = $updateResult['success'] ? 'Yes' : 'No';
        echo "✅ Contact updated: {$success}\n";

    } catch (SDKError $e) {
        echo "❌ SDK Error [{$e->getErrorCode()}]: {$e->getMessage()}\n";
    } catch (Exception $e) {
        echo "❌ Unexpected Error: {$e->getMessage()}\n";
    }
}

main(); 