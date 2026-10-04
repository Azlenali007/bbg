<?php
declare(strict_types=1);

namespace Payments\Gateways;

use Exception;

require_once __DIR__ . '/GatewayInterface.php';

class StripeGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initiatePayment(array $paymentData): array
    {
        $secretKey = $this->config['secret_key'] ?? '';
        if (empty($secretKey)) {
            return [
                'success' => false,
                'message' => 'Stripe Secret Key is not configured in payment settings.'
            ];
        }

        $amountInCents = (int)round($paymentData['amount'] * 100);
        $currency = strtolower($paymentData['currency'] ?? 'usd');

        $postFields = http_build_query([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amountInCents,
                    'product_data' => [
                        'name' => 'Account Deposit: ' . $paymentData['transaction_ref'],
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => APP_URL . '/user/add-funds.php?status=success&ref=' . $paymentData['transaction_ref'],
            'cancel_url' => APP_URL . '/user/add-funds.php?status=canceled',
            'client_reference_id' => $paymentData['transaction_ref'],
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string)$res, true);

        if ($httpCode === 200 && isset($json['url'])) {
            return [
                'success' => true,
                'redirect_url' => $json['url'],
                'session_id' => $json['id']
            ];
        }

        return [
            'success' => false,
            'message' => $json['error']['message'] ?? 'Unable to initialize Stripe checkout session.'
        ];
    }

    public function verifyWebhook(array $headers, string $rawPayload): array
    {
        $sigHeader = $headers['stripe-signature'] ?? ($headers['Stripe-Signature'] ?? '');
        $webhookSecret = $this->config['webhook_secret'] ?? '';

        if (empty($sigHeader) || empty($webhookSecret)) {
            return ['success' => false, 'message' => 'Missing webhook credentials'];
        }

        $event = json_decode($rawPayload, true);
        if (!$event) {
            return ['success' => false, 'message' => 'Invalid JSON payload'];
        }

        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            return [
                'success' => true,
                'transaction_ref' => $session['client_reference_id'] ?? null,
                'gateway_transaction_id' => $session['id'] ?? null,
                'amount' => (float)(($session['amount_total'] ?? 0) / 100),
                'payload' => $session
            ];
        }

        return ['success' => false, 'message' => 'Event ignored'];
    }
}
