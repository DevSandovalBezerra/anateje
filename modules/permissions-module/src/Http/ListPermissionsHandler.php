<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Http;

use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\PermissionsModule\Application\ListPermissions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ListPermissionsHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListPermissions $useCase,
        private PermissionChecker $permissions,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.permissoes.view');

        $result = $this->useCase->execute();

        return $this->responses->json([
            'ok' => true,
            'data' => $result
        ]);
    }
}
