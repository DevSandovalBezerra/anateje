<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule\Tests;

use Anateje\BenefitsModule\Application\ListBenefits;
use Anateje\BenefitsModule\Http\ListBenefitsHandler;
use Anateje\Contracts\DbConnection;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\ResponseFactory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class ListBenefitsHandlerTest extends TestCase
{
    public function testShouldRequirePermission(): void
    {
        $permissions = $this->createMock(PermissionChecker::class);
        $permissions->expects($this->once())
            ->method('requirePermission')
            ->with('admin.beneficios.view')
            ->willThrowException(new \RuntimeException('Acesso negado', 403));

        $db = $this->createMock(DbConnection::class);
        $useCase = new ListBenefits($db);
        $responses = $this->createMock(ResponseFactory::class);

        $handler = new ListBenefitsHandler($useCase, $permissions, $responses);
        $request = new ServerRequest('GET', '/api/v2/benefits');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);

        $handler->handle($request);
    }
}

