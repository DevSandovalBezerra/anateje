<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\AuthContextProvider;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;
use Throwable;

final class DeleteMember
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit,
        private AuthContextProvider $auth,
        private GetMember $getMember
    ) {
    }

    public function execute(int $id): array
    {
        $pdo = $this->db->getPdo();
        $userId = (int) ($this->auth->getUserId() ?? 0);

        if ($id <= 0) {
            return ['ok' => false, 'error_code' => 'VALIDATION'];
        }

        $pdo->beginTransaction();
        try {
            $before = $this->getMember->fetchDetail($pdo, $id);
            if (!$before) {
                throw new RuntimeException('MEMBER_NOT_FOUND');
            }

            $pdo->prepare('DELETE FROM addresses WHERE member_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM member_benefits WHERE member_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM event_registrations WHERE member_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM members WHERE id = ?')->execute([$id]);
            $pdo->prepare('UPDATE usuarios SET ativo = 0 WHERE id = ?')->execute([(int) $before['user_id']]);

            $hist = $pdo->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
                VALUES (?, ?, ?, ?, ?)');
            $hist->execute([
                $id,
                (string) ($before['status'] ?? 'ATIVO'),
                'INATIVO',
                $userId,
                'Exclusao administrativa',
            ]);

            $this->audit->log('admin.associados', 'delete', 'member', $id, $before, [], []);

            $pdo->commit();
            return ['ok' => true];
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error_code' => $e->getMessage()];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error_code' => 'FAIL', 'error_message' => $e->getMessage()];
        }
    }
}

