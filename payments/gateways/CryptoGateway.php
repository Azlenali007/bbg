<?php
declare(strict_types=1);

namespace Payments\Gateways;

require_once __DIR__ . '/GatewayInterface.php';

class CryptoGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initiatePayment(array $paymentData): array
    {
        $walletAddress = $this->config['wallet_address'] ?? 'USDT-TRC20-TJ9vK...EXAMPLE';
        $network = $this->config['network'] ?? 'USDT (TRC-20)';

        return [
            'success' => true,
            'instructions' => "Please transfer exactly {$paymentData['amount']} USD equivalent in {$network} to address below.",
            'wallet_address' => $walletAddress,
            'network' => $network,
            'transaction_ref' => $paymentData['transaction_ref']
        ];
    }

    public function verifyWebhook(array $headers, string $rawPayload): array
    {
        $data = json_decode($rawPayload, true);
        if (!empty($data['tx_hash']) && !empty($data['ref'])) {
            return [
                'success' => true,
                'transaction_ref' => $data['ref'],
                'gateway_transaction_id' => $data['tx_hash'],
                'amount' => (float)($data['amount'] ?? 0),
                'payload' => $data
            ];
        }
        return ['success' => false, 'message' => 'Invalid crypto webhook'];
    }
}
