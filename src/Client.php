<?php

declare(strict_types=1);

namespace Receeco;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Receeco\Exceptions\SDKError;
use Receeco\Utils\TokenGenerator;

/**
 * ReCeeco API client for creating and managing digital receipts.
 */
class Client
{
    private string $apiKey;
    private string $baseUrl;
    private HttpClient $httpClient;

    /**
     * Initialize the ReCeeco client.
     *
     * @param array $options Configuration options including api_key and optional base_url
     * @throws SDKError If api_key is not provided
     */
    public function __construct(array $options)
    {
        if (empty($options['api_key'])) {
            throw new SDKError('API_KEY_REQUIRED', 'api_key is required');
        }

        $this->apiKey = $options['api_key'];
        $this->baseUrl = $options['base_url'] ?? 'https://receeco.com/api/trpc';

        $this->httpClient = new HttpClient([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Create a digital receipt from transaction data.
     *
     * @param array $inputData Receipt creation data
     * @return array Response with id and token
     * @throws SDKError If the API request fails
     */
    public function createReceipt(array $inputData): array
    {
        // Validate required fields
        $this->validateCreateReceiptInput($inputData);

        // Generate required fields if not provided
        $token = TokenGenerator::generateReceiptToken();
        $shortCode = TokenGenerator::generateShortCode();
        $transactionDate = $inputData['transaction_date'] ?? date('c');

        // Prepare the payload matching the API schema
        $payload = [
            'token' => $token,
            'short_code' => $shortCode,
            'merchant_string_id' => $inputData['merchant_string_id'],
            'merchant_name' => $inputData['merchant_name'] ?? null,
            'merchant_logo' => $inputData['merchant_logo'] ?? null,
            'accent_color' => $inputData['accent_color'] ?? null,
            'customer_email' => $inputData['customer_email'] ?? null,
            'customer_phone' => $inputData['customer_phone'] ?? null,
            'total_amount' => $inputData['total_amount'],
            'currency' => $inputData['currency'] ?? 'NGN',
            'transaction_date' => $transactionDate,
            'items' => $inputData['items'],
            'category' => $inputData['category'],
            'payment_method' => $inputData['payment_method'] ?? null,
            'location' => $inputData['location'] ?? null,
            'status' => 'completed',
        ];

        return $this->post('createReceiptFromPOS', $payload);
    }

    /**
     * Get a receipt by its token or short code.
     *
     * @param string $tokenOrCode Receipt token or 6-digit short code
     * @return array Complete receipt data
     * @throws SDKError If the receipt is not found or API request fails
     */
    public function getReceipt(string $tokenOrCode): array
    {
        return $this->get('getReceipt', ['token' => $tokenOrCode]);
    }

    /**
     * Update contact details (email/phone) attached to a receipt.
     *
     * @param array $inputData Contact update data with token and optional email/phone
     * @return array Success status
     * @throws SDKError If the API request fails
     */
    public function updateReceiptContact(array $inputData): array
    {
        if (empty($inputData['token'])) {
            throw new SDKError('INVALID_INPUT', 'token is required');
        }

        return $this->post('updateReceiptContact', $inputData);
    }

    /**
     * Make a GET request to the API.
     *
     * @param string $endpoint API endpoint
     * @param array|null $params Query parameters
     * @return array API response data
     * @throws SDKError If the request fails
     */
    private function get(string $endpoint, ?array $params = null): array
    {
        try {
            $queryParams = [];
            if ($params) {
                $queryParams['input'] = json_encode($params);
            }

            $response = $this->httpClient->get($endpoint, [
                'query' => $queryParams
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            throw new SDKError('REQUEST_FAILED', $e->getMessage());
        }
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array API response data
     * @throws SDKError If the request fails
     */
    private function post(string $endpoint, array $data): array
    {
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => $data
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            throw new SDKError('REQUEST_FAILED', $e->getMessage());
        }
    }

    /**
     * Handle API response and extract data or throw errors.
     *
     * @param \Psr\Http\Message\ResponseInterface $response HTTP response
     * @return array Response data
     * @throws SDKError If the response contains an error
     */
    private function handleResponse($response): array
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SDKError('INVALID_RESPONSE', 'Invalid JSON response from API');
        }

        // Handle tRPC success response
        if ($response->getStatusCode() === 200) {
            if (isset($data['result']['data'])) {
                return $data['result']['data'];
            } elseif (isset($data['result'])) {
                return $data['result'];
            }
            return $data;
        }

        // Handle tRPC error response
        if (isset($data['error'])) {
            $errorInfo = $data['error'];
            $code = $errorInfo['data']['code'] ?? $errorInfo['code'] ?? 'UNKNOWN';
            $message = $errorInfo['message'] ?? 'Unknown error occurred';
            throw new SDKError($code, $message);
        }

        throw new SDKError('UNKNOWN_ERROR', 'Unexpected response format');
    }

    /**
     * Validate input data for createReceipt method.
     *
     * @param array $inputData Input data to validate
     * @throws SDKError If validation fails
     */
    private function validateCreateReceiptInput(array $inputData): void
    {
        $required = ['merchant_string_id', 'items', 'total_amount', 'category'];

        foreach ($required as $field) {
            if (!isset($inputData[$field])) {
                throw new SDKError('INVALID_INPUT', "Field '{$field}' is required");
            }
        }

        if (!is_array($inputData['items']) || empty($inputData['items'])) {
            throw new SDKError('INVALID_INPUT', 'items must be a non-empty array');
        }

        foreach ($inputData['items'] as $index => $item) {
            $itemRequired = ['name', 'quantity', 'unit_price', 'total_price'];
            foreach ($itemRequired as $field) {
                if (!isset($item[$field])) {
                    throw new SDKError('INVALID_INPUT', "Item {$index}: field '{$field}' is required");
                }
            }
        }
    }
} 