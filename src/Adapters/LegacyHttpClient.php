<?php

declare(strict_types=1);

namespace Anateje\Adapters;

use Anateje\Contracts\HttpClient;

final class LegacyHttpClient implements HttpClient
{
    public function postJson(string $url, array $payload, array $headers = [], int $timeoutSeconds = 15): array
    {
        require_once dirname(__DIR__, 2) . '/api/v1/_bootstrap.php';

        if (!function_exists('anateje_http_post_json')) {
            return ['ok' => false, 'error' => 'http_client_unavailable'];
        }

        return anateje_http_post_json($url, $payload, $headers, $timeoutSeconds);
    }
}

