<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\AuthContextProvider;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;
use Throwable;

final class DeleteBenefit
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit,
        private AuthContextProvider $auth
    ) {
    }

    public function execute(int $id): array
    {
        $pdo = $this->db->getPdo();
        $actorId = (int) ($this->auth->getUserId() ?? 0);

        if ($id <= 0) {
            return ['ok' => false, 'error_code' => 'VALIDATION', 'status' => 422];
        }

        $pdo->beginTransaction();
        try {
            $sb = $pdo->prepare('SELECT * FROM benefits WHERE id = ? LIMIT 1');
            $sb->execute([$id]);
            $before = $sb->fetch(PDO::FETCH_ASSOC);
            if (!$before) {
                return ['ok' => false, 'error_code' => 'NOT_FOUND', 'status' => 404];
            }

            $pdo->prepare('DELETE FROM member_benefits WHERE benefit_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM benefits WHERE id = ?')->execute([$id]);

            $this->audit->log('admin.beneficios', 'delete', 'benefit', $id, $before, [], ['actor_id' => $actorId]);

            $pdo->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Falha ao excluir beneficio', 500);
        }
    }
}

