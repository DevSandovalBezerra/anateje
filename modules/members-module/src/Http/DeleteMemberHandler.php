<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Http;

use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\MembersModule\Application\DeleteMember;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteMemberHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteMember $useCase,
        private PermissionChecker $permissions,
        private CsrfValidator $csrf,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.associados.delete');
        $this->csrf->generateToken();
        $this->csrf->requireValidToken();

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $id = (int) ($body['id'] ?? 0);
        $result = $this->useCase->execute($id);

        if (!empty($result['ok'])) {
            return $this->responses->json(['ok' => true, 'data' => ['deleted' => true]]);
        }

        $errCode = (string) ($result['error_code'] ?? 'FAIL');
        if ($errCode === 'MEMBER_NOT_FOUND') {
            return $this->responses->json(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Associado nao encontrado']], 404);
        }

        if ($errCode === 'VALIDATION') {
            return $this->responses->json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'ID invalido']], 422);
        }

        return $this->responses->json(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => 'Falha ao excluir associado']], 500);
    }
}

