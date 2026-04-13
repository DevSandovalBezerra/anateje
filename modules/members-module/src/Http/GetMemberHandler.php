<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Http;

use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\MembersModule\Application\GetMember;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetMemberHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetMember $useCase,
        private PermissionChecker $permissions,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.associados.view');

        $id = (int) $request->getAttribute('id', 0);
        if ($id <= 0) {
            return $this->responses->json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'ID invalido']], 422);
        }

        $result = $this->useCase->execute($id);
        if (empty($result['found'])) {
            return $this->responses->json(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Associado nao encontrado']], 404);
        }

        return $this->responses->json([
            'ok' => true,
            'data' => [
                'member' => $result['member'],
                'status_history' => $result['status_history'],
            ],
        ]);
    }
}

