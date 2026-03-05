<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiRunner;

final class MemberFoldersUploadValidationTest extends TestCase
{
    public function testUploadRejectsInvalidDestinationFolder(): void
    {
        $result = ApiRunner::run([
            'endpoint' => 'api/v1/member_folders.php',
            'method' => 'POST',
            'auth' => true,
            'get' => ['action' => 'admin_upload_file'],
            'post' => ['folder_id' => 0],
            'files' => [
                'arquivo' => [
                    'name' => 'comprovante.pdf',
                    'type' => 'application/pdf',
                    'content' => 'pdf-content',
                    'size' => 12,
                    'error' => UPLOAD_ERR_OK,
                ],
            ],
        ]);

        self::assertSame(422, $result['status'], $result['stdout'] . PHP_EOL . $result['stderr']);
        self::assertIsArray($result['json'], 'Resposta JSON invalida: ' . $result['stdout']);
        self::assertSame('VALIDATION', (string) ($result['json']['error']['code'] ?? ''));
        self::assertSame('Pasta de destino invalida', (string) ($result['json']['error']['message'] ?? ''));
    }

    public function testUploadRejectsDisallowedExtension(): void
    {
        $result = ApiRunner::run([
            'endpoint' => 'api/v1/member_folders.php',
            'method' => 'POST',
            'auth' => true,
            'ensure_schema' => true,
            'pre_sql' => [
                'DELETE FROM member_folders WHERE id = 9001',
                "INSERT INTO member_folders (id, member_id, parent_id, tipo, nome, status, created_by) VALUES (9001, NULL, NULL, 'folder', 'Pasta Upload Teste', 'active', 1)",
            ],
            'get' => ['action' => 'admin_upload_file'],
            'post' => ['folder_id' => 9001],
            'files' => [
                'arquivo' => [
                    'name' => 'script.php',
                    'type' => 'application/octet-stream',
                    'content' => '<?php echo 1; ?>',
                    'size' => 20,
                    'error' => UPLOAD_ERR_OK,
                ],
            ],
        ]);

        self::assertSame(422, $result['status'], $result['stdout'] . PHP_EOL . $result['stderr']);
        self::assertIsArray($result['json'], 'Resposta JSON invalida: ' . $result['stdout']);
        self::assertSame('VALIDATION', (string) ($result['json']['error']['code'] ?? ''));
        self::assertSame('Extensao nao permitida', (string) ($result['json']['error']['message'] ?? ''));
    }

    public function testUploadRejectsFileAboveSizeLimit(): void
    {
        $result = ApiRunner::run([
            'endpoint' => 'api/v1/member_folders.php',
            'method' => 'POST',
            'auth' => true,
            'ensure_schema' => true,
            'pre_sql' => [
                'DELETE FROM member_folders WHERE id = 9002',
                "INSERT INTO member_folders (id, member_id, parent_id, tipo, nome, status, created_by) VALUES (9002, NULL, NULL, 'folder', 'Pasta Upload Limite', 'active', 1)",
            ],
            'get' => ['action' => 'admin_upload_file'],
            'post' => ['folder_id' => 9002],
            'files' => [
                'arquivo' => [
                    'name' => 'arquivo.pdf',
                    'type' => 'application/pdf',
                    'content' => 'x',
                    'size' => (25 * 1024 * 1024) + 1,
                    'error' => UPLOAD_ERR_OK,
                ],
            ],
        ]);

        self::assertSame(422, $result['status'], $result['stdout'] . PHP_EOL . $result['stderr']);
        self::assertIsArray($result['json'], 'Resposta JSON invalida: ' . $result['stdout']);
        self::assertSame('VALIDATION', (string) ($result['json']['error']['code'] ?? ''));
        self::assertSame('Arquivo excede o limite de 25MB', (string) ($result['json']['error']['message'] ?? ''));
    }
}

