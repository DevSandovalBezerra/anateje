<?php

declare(strict_types=1);

namespace Anateje\IntegrationsModule\Application;

use Anateje\Contracts\HttpClient;
use RuntimeException;

final class TestIntegration
{
    public function __construct(
        private ListIntegrations $list,
        private HttpClient $http
    ) {
    }

    public function execute(string $provider): array
    {
        $provider = strtoupper(trim($provider));
        if (!in_array($provider, ['MAILCHIMP', 'WHATSAPP'], true)) {
            throw new RuntimeException('Provider invalido', 422);
        }

        $providers = $this->list->execute();
        $cfg = null;
        foreach ($providers as $row) {
            if (strtoupper((string) ($row['provider'] ?? '')) === $provider) {
                $cfg = $row;
                break;
            }
        }

        if (!$cfg) {
            throw new RuntimeException('Configuracao nao encontrada', 404);
        }

        if ((int) ($cfg['enabled'] ?? 0) !== 1) {
            throw new RuntimeException('Integracao desativada', 422);
        }

        $apiKey = trim((string) ($cfg['api_key'] ?? ''));
        $endpoint = trim((string) ($cfg['endpoint'] ?? ''));
        if ($apiKey === '' || $endpoint === '') {
            throw new RuntimeException('Informe API key e endpoint para testar', 422);
        }

        $config = is_array($cfg['config'] ?? null) ? $cfg['config'] : [];
        $extraHeaders = [];
        $extraHeaders[] = 'Authorization: Bearer ' . $apiKey;
        $extraHeaders[] = 'X-API-Key: ' . $apiKey;

        if (is_array($config['headers'] ?? null)) {
            foreach ($config['headers'] as $h) {
                $h = trim((string) $h);
                if ($h !== '') {
                    $extraHeaders[] = $h;
                }
            }
        }

        $payload = [
            'type' => 'integration_test',
            'provider' => $provider,
            'timestamp' => date('c'),
            'sender' => $cfg['sender'] ?? '',
            'config' => $config,
        ];

        $res = $this->http->postJson($endpoint, $payload, $extraHeaders, 15);
        if (empty($res['ok'])) {
            $msg = 'Teste falhou';
            if (!empty($res['status'])) {
                $msg .= ' (HTTP ' . $res['status'] . ')';
            }
            if (!empty($res['error'])) {
                $msg .= ': ' . $res['error'];
            }
            throw new RuntimeException($msg, 422);
        }

        return [
            'tested' => true,
            'provider' => $provider,
            'message' => 'Teste realizado com sucesso no endpoint configurado.',
            'http_status' => $res['status'] ?? 0,
        ];
    }
}

