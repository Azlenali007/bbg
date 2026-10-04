<?php
declare(strict_types=1);

namespace Providers;

use Database;
use Logger;
use Providers\Adapters\StandardSmmAdapter;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';
require_once BASE_PATH . '/providers/adapters/StandardSmmAdapter.php';

class ProviderManager
{
    public static function getAdapter(int $providerId): ?StandardSmmAdapter
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM providers WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$providerId]);
        $provider = $stmt->fetch();

        if (!$provider) {
            return null;
        }

        return new StandardSmmAdapter($provider['api_url'], $provider['api_key']);
    }

    public static function testConnection(string $apiUrl, string $apiKey): array
    {
        $adapter = new StandardSmmAdapter($apiUrl, $apiKey);
        $res = $adapter->getBalance();

        if (isset($res['error'])) {
            return ['success' => false, 'message' => $res['error']];
        }

        $balance = $res['balance'] ?? ($res['balance_usd'] ?? null);
        if ($balance === null) {
            return ['success' => false, 'message' => 'Connected, but no balance returned. Check API credentials.'];
        }

        return [
            'success' => true,
            'balance' => (float)$balance,
            'currency' => $res['currency'] ?? 'USD'
        ];
    }

    public static function syncBalance(int $providerId): bool
    {
        $adapter = self::getAdapter($providerId);
        if (!$adapter) {
            return false;
        }

        $res = $adapter->getBalance();
        if (isset($res['balance'])) {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE providers SET balance = ?, currency = ?, last_sync = NOW() WHERE id = ?");
            $currency = $res['currency'] ?? 'USD';
            $stmt->execute([(float)$res['balance'], $currency, $providerId]);
            return true;
        }

        return false;
    }

    public static function fetchServices(int $providerId): array
    {
        $adapter = self::getAdapter($providerId);
        if (!$adapter) {
            return ['success' => false, 'message' => 'Provider not found or inactive.'];
        }

        $services = $adapter->getServices();
        if (isset($services['error'])) {
            return ['success' => false, 'message' => $services['error']];
        }

        return ['success' => true, 'services' => $services];
    }
}
