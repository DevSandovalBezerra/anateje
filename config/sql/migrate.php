<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Use somente via CLI.\n";
    exit(1);
}

// Evita headers de navegador ao carregar config compartilhada.
$_SERVER['REQUEST_URI'] = '/api/cli-migrate';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require_once __DIR__ . '/../database.php';

function migrate_out(string $text): void
{
    fwrite(STDOUT, $text . PHP_EOL);
}

function migrate_err(string $text): void
{
    fwrite(STDERR, $text . PHP_EOL);
}

function migrate_usage(): void
{
    migrate_out('Uso: php config/sql/migrate.php [up|status]');
}

function migrate_ensure_table(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        filename VARCHAR(255) NOT NULL,
        checksum CHAR(64) NOT NULL,
        batch INT NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_schema_migrations_filename (filename),
        KEY idx_schema_migrations_batch (batch)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function migrate_files(string $dir): array
{
    $files = glob($dir . '/*.sql');
    if (!is_array($files)) {
        return [];
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    $list = [];
    foreach ($files as $path) {
        $name = basename($path);
        if (stripos($name, 'template') !== false) {
            continue;
        }
        $list[] = $path;
    }

    return $list;
}

function migrate_applied(PDO $db): array
{
    $rows = $db->query('SELECT filename, checksum, batch, applied_at FROM schema_migrations ORDER BY id ASC')
        ->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $row) {
        $map[(string) $row['filename']] = $row;
    }

    return $map;
}

function migrate_split_sql(string $sql): array
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    $lines = preg_split('/\R/', $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $trim = ltrim($line);
        if (strpos($trim, '--') === 0 || strpos($trim, '#') === 0) {
            continue;
        }
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);

    $stmts = [];
    $buffer = '';
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $escaped = false;

    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $buffer .= $ch;

        if ($escaped) {
            $escaped = false;
            continue;
        }

        if ($ch === '\\') {
            $escaped = true;
            continue;
        }

        if (!$inDouble && !$inBacktick && $ch === "'") {
            $inSingle = !$inSingle;
            continue;
        }
        if (!$inSingle && !$inBacktick && $ch === '"') {
            $inDouble = !$inDouble;
            continue;
        }
        if (!$inSingle && !$inDouble && $ch === '`') {
            $inBacktick = !$inBacktick;
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick && $ch === ';') {
            $stmt = trim(substr($buffer, 0, -1));
            if ($stmt !== '') {
                $stmts[] = $stmt;
            }
            $buffer = '';
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $stmts[] = $tail;
    }

    return $stmts;
}

function migrate_run_up(PDO $db, array $files, array $applied): int
{
    $pending = [];
    $maxBatch = 0;

    foreach ($applied as $meta) {
        $b = (int) ($meta['batch'] ?? 0);
        if ($b > $maxBatch) {
            $maxBatch = $b;
        }
    }

    foreach ($files as $path) {
        $name = basename($path);
        $checksum = hash_file('sha256', $path) ?: '';

        if (isset($applied[$name])) {
            if ((string) $applied[$name]['checksum'] !== $checksum) {
                migrate_err("Checksum divergente para migration ja aplicada: $name");
                migrate_err('Crie uma nova migration incremental em vez de alterar historico.');
                return 1;
            }
            continue;
        }

        $pending[] = [
            'path' => $path,
            'name' => $name,
            'checksum' => $checksum,
        ];
    }

    if (count($pending) === 0) {
        migrate_out('Sem migrations pendentes.');
        return 0;
    }

    $batch = $maxBatch + 1;
    migrate_out('Batch: ' . $batch);

    foreach ($pending as $item) {
        $sql = file_get_contents($item['path']);
        if ($sql === false) {
            migrate_err('Falha ao ler arquivo: ' . $item['name']);
            return 1;
        }

        $stmts = migrate_split_sql($sql);
        migrate_out('Aplicando ' . $item['name'] . ' ...');

        try {
            $db->beginTransaction();
            foreach ($stmts as $stmt) {
                $db->exec($stmt);
            }

            $ins = $db->prepare('INSERT INTO schema_migrations (filename, checksum, batch) VALUES (?,?,?)');
            $ins->execute([$item['name'], $item['checksum'], $batch]);
            $db->commit();

            migrate_out('OK (' . count($stmts) . ' statements)');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            migrate_err('Erro em ' . $item['name'] . ': ' . $e->getMessage());
            return 1;
        }
    }

    migrate_out('Migracoes aplicadas com sucesso.');
    return 0;
}

function migrate_run_status(array $files, array $applied): int
{
    if (count($files) === 0) {
        migrate_out('Nenhuma migration encontrada.');
        return 0;
    }

    foreach ($files as $path) {
        $name = basename($path);
        if (isset($applied[$name])) {
            $meta = $applied[$name];
            migrate_out($name . ' | APPLIED | batch=' . (int) $meta['batch'] . ' | at=' . (string) $meta['applied_at']);
        } else {
            migrate_out($name . ' | PENDING');
        }
    }

    return 0;
}

$command = strtolower((string) ($argv[1] ?? 'up'));
if (!in_array($command, ['up', 'status'], true)) {
    migrate_usage();
    exit(1);
}

$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
migrate_ensure_table($db);

$dir = __DIR__ . '/migrations';
$files = migrate_files($dir);
$applied = migrate_applied($db);

if ($command === 'status') {
    exit(migrate_run_status($files, $applied));
}

exit(migrate_run_up($db, $files, $applied));
