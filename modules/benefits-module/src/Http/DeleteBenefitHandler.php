<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule\Http;

use Anateje\BenefitsModule\Application\DeleteBenefit;
use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class DeleteBenefitHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteBenefit $useCase,
        private PermissionChecker $permissions,
        private CsrfValidator $csrf,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.beneficios.delete');
        $this->csrf->generateToken();
        $this->csrf->requireValidToken();

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $id = (int) ($body['id'] ?? 0);

        try {
            $result = $this->useCase->execute($id);
        } catch (RuntimeException $e) {
            return $this->responses->json(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => $e->getMessage()]], 500);
        }

        if (!empty($result['ok'])) {
            return $this->responses->json(['ok' => true, 'data' => ['deleted' => true]]);
        }

        $status = (int) ($result['status'] ?? 422);
        $code = (string) ($result['error_code'] ?? 'FAIL');
        $message = $code === 'NOT_FOUND' ? 'Beneficio nao encontrado' : ($code === 'VALIDATION' ? 'ID invalido' : 'Falha ao excluir beneficio');
        $outCode = $code === 'NOT_FOUND' ? 'NOT_FOUND' : ($code === 'VALIDATION' ? 'VALIDATION' : 'FAIL');

        return $this->responses->json(['ok' => false, 'error' => ['code' => $outCode, 'message' => $message]], $status);
    }
}

