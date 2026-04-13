<?php

declare(strict_types=1);

namespace Anateje\AuditModule\Http;

use Anateje\AuditModule\Application\ListAuditLogs;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ListAuditLogsHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListAuditLogs $useCase,
        private PermissionChecker $permissions,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->permissions->requirePermission('admin.auditoria.view');

        $queryParams = $request->getQueryParams();
        
        $filters = [
            'module' => $this->sanitizeString($queryParams['module'] ?? ''),
            'operation' => $this->sanitizeString($queryParams['operation'] ?? ''),
            'entity' => $this->sanitizeString($queryParams['entity'] ?? ''),
            'user_id' => max(0, (int) ($queryParams['user_id'] ?? 0)),
            'date_from' => $this->sanitizeDate($queryParams['date_from'] ?? ''),
            'date_to' => $this->sanitizeDate($queryParams['date_to'] ?? ''),
            'q' => substr(trim((string) ($queryParams['q'] ?? '')), 0, 120),
        ];

        $pagination = [
            'page' => (int) ($queryParams['page'] ?? 1),
            'per_page' => (int) ($queryParams['per_page'] ?? 30),
        ];

        $result = $this->useCase->execute($filters, $pagination);

        return $this->responses->json([
            'ok' => true,
            'data' => $result
        ]);
    }

    private function sanitizeString(mixed $val): string
    {
        $str = trim((string) $val);
        if ($str !== '' && !preg_match('/^[a-z0-9._-]{2,80}$/i', $str)) {
            return '';
        }
        return $str;
    }

    private function sanitizeDate(mixed $val): string
    {
        $str = trim((string) $val);
        if ($str !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return '';
        }
        return $str;
    }
}
