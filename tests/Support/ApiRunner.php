<?php
declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class ApiRunner
{
    /**
     * @param array<string,mixed> $options
     * @return array{exit_code:int,status:int,stdout:string,stderr:string,json:array<string,mixed>|null}
     */
    public static function run(array $options): array
    {
        $script = self::projectRoot() . '/tests/support/run_endpoint.php';
        if (!is_file($script)) {
            throw new RuntimeException('Runner nao encontrado: ' . $script);
        }

        $payload = base64_encode((string) json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $cmd = [PHP_BINARY, $script, $payload];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        putenv('DB_HOST=' . (string) TEST_DB_HOST);
        putenv('DB_NAME=' . (string) TEST_DB_NAME);
        putenv('DB_USER=' . (string) TEST_DB_USER);
        putenv('DB_PASS=' . (string) TEST_DB_PASS);

        $process = proc_open($cmd, $descriptors, $pipes, self::projectRoot(), null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('Falha ao iniciar subprocesso de teste da API');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $status = 0;
        if (preg_match('/__HTTP_STATUS__=(\d+)/', (string) $stderr, $m) === 1) {
            $status = (int) $m[1];
        }

        $json = null;
        $trimmed = trim((string) $stdout);
        if ($trimmed !== '') {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        return [
            'exit_code' => (int) $exitCode,
            'status' => $status,
            'stdout' => (string) $stdout,
            'stderr' => (string) $stderr,
            'json' => $json,
        ];
    }

    private static function projectRoot(): string
    {
        /** @var string $root */
        $root = TEST_PROJECT_ROOT;
        return $root;
    }
}
