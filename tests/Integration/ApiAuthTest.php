<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiRunner;

final class ApiAuthTest extends TestCase
{
    public function testMemberFoldersRequiresAuthentication(): void
    {
        $result = ApiRunner::run([
            'endpoint' => 'api/v1/member_folders.php',
            'method' => 'GET',
            'get' => ['action' => 'admin_tree'],
        ]);

        self::assertSame(401, $result['status'], $result['stdout'] . PHP_EOL . $result['stderr']);
        self::assertIsArray($result['json'], 'Resposta JSON invalida: ' . $result['stdout']);
        self::assertFalse((bool) ($result['json']['ok'] ?? true));
        self::assertSame('UNAUTH', (string) ($result['json']['error']['code'] ?? ''));
    }
}

