<?php

declare(strict_types=1);

namespace Anateje\Adapters;

use Anateje\Contracts\DbConnection;
use PDO;

final class LegacyDbConnection implements DbConnection
{
    public function getPdo(): PDO
    {
        require_once dirname(__DIR__, 2) . '/config/database.php';
        require_once dirname(__DIR__, 2) . '/api/v1/_bootstrap.php';

        $pdo = getDB();
        if (function_exists('anateje_ensure_schema')) {
            anateje_ensure_schema($pdo);
        }

        return $pdo;
    }
}
