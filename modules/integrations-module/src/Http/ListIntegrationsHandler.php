<?php

declare(strict_types=1);

namespace Anateje\IntegrationsModule\Http;

use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\IntegrationsModule\Application\ListIntegrations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ListIntegrationsHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListIntegrations $useCase,
        private PermissionChecker $permissions,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.integracoes.view');

        $result = $this->useCase->execute();

        return $this->responses->json([
            'ok' => true,
            'data' => [
                'providers' => $result
            ]
        ]);
    }
}
