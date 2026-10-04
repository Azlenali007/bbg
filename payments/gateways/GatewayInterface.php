<?php
declare(strict_types=1);

namespace Payments\Gateways;

interface GatewayInterface
{
    public function initiatePayment(array $paymentData): array;
    public function verifyWebhook(array $headers, string $rawPayload): array;
}
