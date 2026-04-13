<?php

declare(strict_types=1);

namespace Anateje\IntegrationsModule\Http;

use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\IntegrationsModule\Application\SaveIntegration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class SaveIntegrationHandler implements RequestHandlerInterface
{
    public function __construct(
        private SaveIntegration $useCase,
        private PermissionChecker $permissions,
        private CsrfValidator $csrf,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.integracoes.edit');
        $this->csrf->requireValidToken();

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $provider = strtoupper(trim((string) ($body['provider'] ?? '')));
        $enabled = !empty($body['enabled']);
        $apiKey = trim((string) ($body['api_key'] ?? ''));
        $endpoint = trim((string) ($body['endpoint'] ?? ''));
        $sender = trim((string) ($body['sender'] ?? ''));

        $config = $body['config'] ?? [];
        if (is_string($config)) {
            $parsed = json_decode($config, true);
            $config = is_array($parsed) ? $parsed : [];
        }
        if (!is_array($config)) {
            $config = [];
        }

        try {
            $result = $this->useCase->execute($provider, $enabled, $apiKey, $endpoint, $sender, $config);
            return $this->responses->json([
                'ok' => true,
                'data' => [
                    'saved' => true,
                    'provider' => $provider,
                    'providers' => $result
                ]
            ]);
        } catch (RuntimeException $e) {
            $code = $e->getCode();
            return $this->responses->json([
                'ok' => false,
                'error' => [
                    'code' => $code === 422 ? 'VALIDATION' : 'FAIL',
                    'message' => $e->getMessage()
                ]
            ], $code > 0 ? $code : 500);
        }
    }
}
