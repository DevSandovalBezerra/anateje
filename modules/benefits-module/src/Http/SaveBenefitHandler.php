<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule\Http;

use Anateje\BenefitsModule\Application\SaveBenefit;
use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class SaveBenefitHandler implements RequestHandlerInterface
{
    public function __construct(
        private SaveBenefit $useCase,
        private PermissionChecker $permissions,
        private CsrfValidator $csrf,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->csrf->generateToken();
        $this->csrf->requireValidToken();

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $id = (int) ($body['id'] ?? 0);
        $this->permissions->requirePermission($id > 0 ? 'admin.beneficios.edit' : 'admin.beneficios.create');

        try {
            $result = $this->useCase->execute($body);
        } catch (RuntimeException $e) {
            return $this->responses->json(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => $e->getMessage()]], 500);
        }

        if (!empty($result['ok'])) {
            return $this->responses->json(['ok' => true, 'data' => $result['data'] ?? []]);
        }

        $status = (int) ($result['status'] ?? 422);
        return $this->responses->json(
            ['ok' => false, 'error' => ['code' => (string) ($result['error_code'] ?? 'VALIDATION'), 'message' => (string) ($result['error_message'] ?? 'Dados invalidos')]],
            $status
        );
    }
}

