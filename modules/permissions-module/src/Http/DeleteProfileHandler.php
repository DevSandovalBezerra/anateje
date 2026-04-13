<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Http;

use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\PermissionsModule\Application\DeleteProfile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class DeleteProfileHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteProfile $useCase,
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

        $id = (int) ($body['id'] ?? 0);
        $reassignProfileId = (int) ($body['reassign_profile_id'] ?? 0);

        try {
            $result = $this->useCase->execute($id, $reassignProfileId);
            return $this->responses->json([
                'ok' => true,
                'data' => $result
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
