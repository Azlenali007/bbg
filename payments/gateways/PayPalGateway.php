<?php
declare(strict_types=1);

namespace Payments\Gateways;

require_once __DIR__ . '/GatewayInterface.php';

class PayPalGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initiatePayment(array $paymentData): array
    {
        $clientId = $this->config['client_id'] ?? '';
        $secret = $this->config['secret'] ?? '';
        $isSandbox = !empty($this->config['sandbox']);

        if (empty($clientId) || empty($secret)) {
            return [
                'success' => false,
                'message' => 'PayPal credentials are not configured.'
            ];
        }

        $baseUrl = $isSandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        // 1. Get access token
        $ch = curl_init("{$baseUrl}/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, "{$clientId}:{$secret}");
        $tokenRes = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode((string)$tokenRes, true);
        $token = $tokenData['access_token'] ?? null;

        if (!$token) {
            return ['success' => false, 'message' => 'Could not authenticate with PayPal API.'];
        }

        // 2. Create Order
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $paymentData['transaction_ref'],
                'amount' => [
                    'currency_code' => $paymentData['currency'] ?? 'USD',
                    'value' => number_format($paymentData['amount'], 2, '.', '')
                ]
            ]],
            'application_context' => [
                'return_url' => APP_URL . '/user/add-funds.php?status=success&ref=' . $paymentData['transaction_ref'],
                'cancel_url' => APP_URL . '/user/add-funds.php?status=canceled',
                'brand_name' => 'ApexSMM'
            ]
        ];

        $ch = curl_init("{$baseUrl}/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $orderRes = curl_exec($ch);
        curl_close($ch);

        $orderData = json_decode((string)$orderRes, true);

        if (isset($orderData['links'])) {
            foreach ($orderData['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return [
                        'success' => true,
                        'redirect_url' => $link['href'],
                        'order_id' => $orderData['id']
                    ];
                }
            }
        }

        return ['success' => false, 'message' => 'Could not generate PayPal approval link.'];
    }

    public function verifyWebhook(array $headers, string $rawPayload): array
    {
        $data = json_decode($rawPayload, true);
        if (($data['event_type'] ?? '') === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $data['resource'] ?? [];
            return [
                'success' => true,
                'transaction_ref' => $resource['custom_id'] ?? null,
                'gateway_transaction_id' => $resource['id'] ?? null,
                'amount' => (float)($resource['amount']['value'] ?? 0),
                'payload' => $resource
            ];
        }
        return ['success' => false, 'message' => 'Not a captured payment event.'];
    }
}
