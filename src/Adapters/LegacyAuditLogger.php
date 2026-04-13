<?php

declare(strict_types=1);

namespace Anateje\Adapters;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\DbConnection;
use Anateje\Contracts\AuthContextProvider;

final class LegacyAuditLogger implements AuditLogger
{
    public function __construct(
        private DbConnection $db,
        private AuthContextProvider $auth
    ) {
    }

    public function log(
        string $modulo,
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        array $before = [],
        array $after = [],
        array $meta = []
    ): void {
        $pdo = $this->db->getPdo();
        $userId = $this->auth->getUserId();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $jsonBefore = empty($before) ? null : json_encode($before, JSON_UNESCAPED_UNICODE);
        $jsonAfter = empty($after) ? null : json_encode($after, JSON_UNESCAPED_UNICODE);
        $jsonMeta = empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE);

        $sql = "INSERT INTO audit_logs (
            user_id, modulo, acao, entidade, entidade_id, ip, user_agent, payload_before, payload_after, meta_data, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $st = $pdo->prepare($sql);
        $st->execute([
            $userId,
            $modulo,
            $acao,
            $entidade,
            $entidadeId,
            $ip,
            $userAgent,
            $jsonBefore,
            $jsonAfter,
            $jsonMeta
        ]);
    }
}
