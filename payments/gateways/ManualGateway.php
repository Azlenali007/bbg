<?php
declare(strict_types=1);

namespace Payments\Gateways;

require_once __DIR__ . '/GatewayInterface.php';

class ManualGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initiatePayment(array $paymentData): array
    {
        $instructions = $this->config['instructions'] ?? 'Please send payment via bank transfer or local wire, then open a ticket with your deposit reference.';
        return [
            'success' => true,
            'instructions' => $instructions,
            'transaction_ref' => $paymentData['transaction_ref']
        ];
    }

    public function verifyWebhook(array $headers, string $rawPayload): array
    {
        return ['success' => false, 'message' => 'Manual gateways do not support webhooks. Use admin panel to approve.'];
    }
}
