<?php

declare(strict_types=1);

namespace Anateje\AuditModule\Tests;

use Anateje\AuditModule\Application\ListAuditLogs;
use Anateje\AuditModule\Http\ListAuditLogsHandler;
use Anateje\Contracts\DbConnection;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ListAuditLogsHandlerTest extends TestCase
{
    public function testShouldDenyAccessIfNoPermission(): void
    {
        $permissions = $this->createMock(PermissionChecker::class);
        $permissions->expects($this->once())
            ->method('requirePermission')
            ->with('admin.auditoria.view')
            ->willThrowException(new \RuntimeException('Acesso negado', 403));

        $db = $this->createMock(DbConnection::class);
        $useCase = new ListAuditLogs($db);

        $responses = $this->createMock(ResponseFactory::class);
        
        $handler = new ListAuditLogsHandler($useCase, $permissions, $responses);
        $request = new ServerRequest('GET', '/api/v2/audit');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        
        $handler->handle($request);
    }
}
