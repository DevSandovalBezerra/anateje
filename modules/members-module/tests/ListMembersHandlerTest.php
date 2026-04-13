<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Tests;

use Anateje\Contracts\DbConnection;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Anateje\MembersModule\Application\ListMembers;
use Anateje\MembersModule\Http\ListMembersHandler;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class ListMembersHandlerTest extends TestCase
{
    public function testShouldRequirePermission(): void
    {
        $permissions = $this->createMock(PermissionChecker::class);
        $permissions->expects($this->once())
            ->method('requirePermission')
            ->with('admin.associados.view')
            ->willThrowException(new \RuntimeException('Acesso negado', 403));

        $db = $this->createMock(DbConnection::class);
        $useCase = new ListMembers($db);

        $responses = $this->createMock(ResponseFactory::class);

        $handler = new ListMembersHandler($useCase, $permissions, $responses);
        $request = new ServerRequest('GET', '/api/v2/members');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

        $handler->handle($request);
    }
}

