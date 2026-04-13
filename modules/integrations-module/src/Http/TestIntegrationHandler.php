<?php

declare(strict_types=1);

namespace Anateje\IntegrationsModule\Http;

use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\IntegrationsModule\Application\TestIntegration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class TestIntegrationHandler implements RequestHandlerInterface
{
    public function __construct(
        private TestIntegration $useCase,
        private PermissionChecker $permissions,
        private CsrfValidator $csrf,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.integracoes.edit');
        $this->csrf->generateToken();
        $this->csrf->requireValidToken();

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $provider = (string) ($body['provider'] ?? '');

        try {
            $result = $this->useCase->execute($provider);
            return $this->responses->json(['ok' => true, 'data' => $result]);
        } catch (RuntimeException $e) {
            $status = (int) $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = 422;
            }
            return $this->responses->json(['ok' => false, 'error' => ['code' => $status === 404 ? 'NOT_FOUND' : 'TEST_FAIL', 'message' => $e->getMessage()]], $status);
        }
    }
}

