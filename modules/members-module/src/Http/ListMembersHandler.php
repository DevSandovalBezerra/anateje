<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Http;

use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\MembersModule\Application\ListMembers;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ListMembersHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListMembers $useCase,
        private PermissionChecker $permissions,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.associados.view');

        $result = $this->useCase->execute($request->getQueryParams());

        return $this->responses->json([
            'ok' => true,
            'data' => $result,
        ]);
    }
}

