<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Http;

use Anateje\Contracts\CsrfValidator;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\MembersModule\Application\SaveMember;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SaveMemberHandler implements RequestHandlerInterface
{
    public function __construct(
        private SaveMember $useCase,
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
        $this->permissions->requirePermission($id > 0 ? 'admin.associados.edit' : 'admin.associados.create');

        $result = $this->useCase->execute($body);
        if (!empty($result['ok'])) {
            return $this->responses->json(['ok' => true, 'data' => $result['data'] ?? []]);
        }

        return $this->responses->json(
            [
                'ok' => false,
                'error' => $this->mapError($result),
            ],
            $this->mapStatus($result)
        );
    }

    private function mapStatus(array $result): int
    {
        $code = (string) ($result['error_code'] ?? '');

        if ($code === 'MEMBER_NOT_FOUND' || $code === 'USER_NOT_FOUND') {
            return 404;
        }

        if ($code === 'CPF_DUPLICADO' || $code === 'MATRICULA_DUPLICADA' || $code === 'EMAIL_FUNCIONAL_DUPLICADO' || $code === 'EMAIL_ACESSO_DUPLICADO' || $code === 'USER_ALREADY_LINKED' || $code === 'ADMIN_USER_NOT_ALLOWED' || $code === 'CEP_REQUIRED') {
            return 422;
        }

        if (str_starts_with($code, 'VALIDATION:')) {
            return 422;
        }

        return 500;
    }

    private function mapError(array $result): array
    {
        $code = (string) ($result['error_code'] ?? 'FAIL');
        $message = 'Falha ao salvar associado';

        if ($code === 'MEMBER_NOT_FOUND') {
            return ['code' => 'NOT_FOUND', 'message' => 'Associado nao encontrado'];
        }
        if ($code === 'USER_NOT_FOUND') {
            return ['code' => 'NOT_FOUND', 'message' => 'Usuario vinculado ao associado nao encontrado'];
        }
        if ($code === 'CPF_DUPLICADO') {
            return ['code' => 'CPF_DUPLICADO', 'message' => 'CPF ja cadastrado'];
        }
        if ($code === 'MATRICULA_DUPLICADA') {
            return ['code' => 'MATRICULA_DUPLICADA', 'message' => 'Registro associativo ja cadastrado'];
        }
        if ($code === 'EMAIL_FUNCIONAL_DUPLICADO') {
            return ['code' => 'EMAIL_FUNCIONAL_DUPLICADO', 'message' => 'Email funcional ja cadastrado'];
        }
        if ($code === 'EMAIL_ACESSO_DUPLICADO') {
            return ['code' => 'EMAIL_ACESSO_DUPLICADO', 'message' => 'Email de acesso ja utilizado por outro usuario'];
        }
        if ($code === 'USER_ALREADY_LINKED') {
            return ['code' => 'USER_ALREADY_LINKED', 'message' => 'Este email ja esta vinculado a outro associado'];
        }
        if ($code === 'ADMIN_USER_NOT_ALLOWED') {
            return ['code' => 'VALIDATION', 'message' => 'Nao e permitido vincular usuario administrador como associado'];
        }
        if ($code === 'CEP_REQUIRED') {
            return ['code' => 'VALIDATION', 'message' => 'CEP e obrigatorio quando houver endereco'];
        }

        if (str_starts_with($code, 'VALIDATION:')) {
            $tag = substr($code, strlen('VALIDATION:'));
            if ($tag === 'NOME') {
                $message = 'Nome e obrigatorio';
            } elseif ($tag === 'CPF') {
                $message = 'CPF invalido';
            } elseif ($tag === 'DATA_FILIACAO') {
                $message = 'Data de filiacao invalida';
            } elseif ($tag === 'EMAIL_FUNCIONAL') {
                $message = 'Email funcional invalido';
            } elseif ($tag === 'CONTRIBUICAO') {
                $message = 'Contribuicao mensal invalida';
            } elseif ($tag === 'MATRICULA') {
                $message = 'Registro associativo invalido';
            } elseif ($tag === 'TELEFONE') {
                $message = 'Telefone invalido';
            } elseif ($tag === 'CEP') {
                $message = 'CEP invalido';
            } elseif ($tag === 'UF') {
                $message = 'UF invalida';
            } elseif ($tag === 'LOGIN_EMAIL') {
                $message = 'Email de acesso invalido';
            } elseif ($tag === 'NOVA_SENHA') {
                $message = 'Nova senha deve ter pelo menos 8 caracteres';
            } else {
                $message = 'Dados invalidos';
            }

            return ['code' => 'VALIDATION', 'message' => $message];
        }

        return [
            'code' => $code !== '' ? $code : 'FAIL',
            'message' => $message,
        ];
    }
}

