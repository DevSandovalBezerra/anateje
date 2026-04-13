<?php

declare(strict_types=1);

namespace Anateje\IntegrationsModule\Application;

use Anateje\Contracts\DbConnection;
use PDO;

final class ListIntegrations
{
    public function __construct(private DbConnection $db)
    {
    }

    public function execute(): array
    {
        $pdo = $this->db->getPdo();

        $rows = [
            'MAILCHIMP' => [
                'provider' => 'MAILCHIMP',
                'enabled' => 0,
                'api_key' => '',
                'endpoint' => '',
                'sender' => '',
                'config' => []
            ],
            'WHATSAPP' => [
                'provider' => 'WHATSAPP',
                'enabled' => 0,
                'api_key' => '',
                'endpoint' => '',
                'sender' => '',
                'config' => []
            ],
        ];

        $this->ensureSchema($pdo);

        $dbRows = $pdo->query('SELECT provider, enabled, api_key, endpoint, sender, config_json, updated_at FROM integration_settings')
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dbRows as $r) {
            $provider = strtoupper((string) $r['provider']);
            if (!isset($rows[$provider])) {
                continue;
            }

            $rows[$provider] = [
                'provider' => $provider,
                'enabled' => (int) $r['enabled'] === 1 ? 1 : 0,
                'api_key' => (string) ($r['api_key'] ?? ''),
                'endpoint' => (string) ($r['endpoint'] ?? ''),
                'sender' => (string) ($r['sender'] ?? ''),
                'config' => $this->decodeJson((string) ($r['config_json'] ?? '')),
                'updated_at' => $r['updated_at'] ?? null,
            ];
        }

        return array_values($rows);
    }

    private function decodeJson(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function ensureSchema(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS integration_settings (
            provider VARCHAR(60) NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            api_key VARCHAR(255) NULL,
            endpoint VARCHAR(255) NULL,
            sender VARCHAR(120) NULL,
            config_json LONGTEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
