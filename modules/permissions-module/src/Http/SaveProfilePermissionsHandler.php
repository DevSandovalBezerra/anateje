<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Http;

use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\PermissionsModule\Application\SaveProfilePermissions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class SaveProfilePermissionsHandler implements RequestHandlerInterface
{
    public function __construct(
        private SaveProfilePermissions $useCase,
        private PermissionChecker $permissions,
        private CsrfValidator $csrf,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.permissoes.edit');
        $this->csrf->requireValidToken();

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $profileId = (int) ($body['profile_id'] ?? 0);
        $permissionIds = $body['permission_ids'] ?? [];
        if (!is_array($permissionIds)) {
            $permissionIds = [];
        }

        try {
            $this->useCase->execute($profileId, $permissionIds);
            return $this->responses->json([
                'ok' => true,
                'data' => [
                    'saved' => true
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
