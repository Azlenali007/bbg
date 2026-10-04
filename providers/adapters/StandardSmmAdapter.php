<?php
declare(strict_types=1);

namespace Providers\Adapters;

use Logger;

require_once BASE_PATH . '/includes/logger.php';

class StandardSmmAdapter
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct(string $apiUrl, string $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    public function getBalance(): array
    {
        return $this->request(['action' => 'balance']);
    }

    public function getServices(): array
    {
        return $this->request(['action' => 'services']);
    }

    public function addOrder(string|int $serviceId, string $link, int $quantity, array $additional = []): array
    {
        $postData = array_merge([
            'action' => 'add',
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity
        ], $additional);

        return $this->request($postData);
    }

    public function getOrderStatus(string|int $orderId): array
    {
        return $this->request([
            'action' => 'status',
            'order' => $orderId
        ]);
    }

    public function refill(string|int $orderId): array
    {
        return $this->request([
            'action' => 'refill',
            'order' => $orderId
        ]);
    }

    public function cancel(string|int $orderId): array
    {
        return $this->request([
            'action' => 'cancel',
            'orders' => $orderId
        ]);
    }

    private function request(array $postData): array
    {
        $postData['key'] = $this->apiKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'ApexSMM-Engine/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            Logger::error("Provider cURL Error: " . $curlError, ['url' => $this->apiUrl]);
            return ['error' => 'Connection failed: ' . $curlError];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Logger::error("Provider HTTP Error {$httpCode}", ['url' => $this->apiUrl, 'response' => substr((string)$response, 0, 500)]);
            return ['error' => "HTTP error code: {$httpCode}"];
        }

        $decoded = json_decode((string)$response, true);
        if ($decoded === null) {
            Logger::error("Provider JSON Parse Error", ['raw' => substr((string)$response, 0, 500)]);
            return ['error' => 'Invalid JSON received from provider API.'];
        }

        return $decoded;
    }
}
