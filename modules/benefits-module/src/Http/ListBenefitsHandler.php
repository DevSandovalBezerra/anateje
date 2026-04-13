<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule\Http;

use Anateje\BenefitsModule\Application\ListBenefits;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ListBenefitsHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListBenefits $useCase,
        private PermissionChecker $permissions,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.beneficios.view');

        return $this->responses->json(['ok' => true, 'data' => $this->useCase->execute()]);
    }
}

