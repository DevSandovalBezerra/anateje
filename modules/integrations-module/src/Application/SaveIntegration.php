<?php

declare(strict_types=1);

namespace Anateje\IntegrationsModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;

final class SaveIntegration
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit
    ) {
    }

    public function execute(string $provider, bool $enabled, string $apiKey, string $endpoint, string $sender, array $config): array
    {
        $pdo = $this->db->getPdo();

        $provider = strtoupper(trim($provider));
        if (!in_array($provider, ['MAILCHIMP', 'WHATSAPP'], true)) {
            throw new RuntimeException('Provider invalido', 422);
        }

        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sql = "INSERT INTO integration_settings (provider, enabled, api_key, endpoint, sender, config_json)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                enabled = VALUES(enabled),
                api_key = VALUES(api_key),
                endpoint = VALUES(endpoint),
                sender = VALUES(sender),
                config_json = VALUES(config_json)";

        $st = $pdo->prepare($sql);
        $st->execute([$provider, (int) $enabled, $apiKey ?: null, $endpoint ?: null, $sender ?: null, $configJson]);

        $this->audit->log('integrations', 'save_integration', 'integration_settings', null, [], [
            'provider' => $provider,
            'enabled' => $enabled
        ]);

        return (new ListIntegrations($this->db))->execute();
    }
}
