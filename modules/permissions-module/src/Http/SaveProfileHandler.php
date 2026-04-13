<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Http;

use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\PermissionsModule\Application\SaveProfile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class SaveProfileHandler implements RequestHandlerInterface
{
    public function __construct(
        private SaveProfile $useCase,
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
        $nome = trim((string) ($body['nome'] ?? ''));
        $descricao = trim((string) ($body['descricao'] ?? ''));
        $ativo = !empty($body['ativo']);

        try {
            $newId = $this->useCase->execute($id, $nome, $descricao, $ativo);
            return $this->responses->json([
                'ok' => true,
                'data' => [
                    'saved' => true,
                    'id' => $newId
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
