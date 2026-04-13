<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;

final class DeleteProfile
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit
    ) {
    }

    public function execute(int $id, int $reassignProfileId): array
    {
        $pdo = $this->db->getPdo();

        if ($id <= 0) {
            throw new RuntimeException('Perfil invalido', 422);
        }
        if ($id === 1) {
            throw new RuntimeException('Perfil Admin nao pode ser removido', 422);
        }

        $stProfile = $pdo->prepare('SELECT id FROM perfis_acesso WHERE id = ? LIMIT 1');
        $stProfile->execute([$id]);
        if (!$stProfile->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Perfil nao encontrado', 404);
        }

        $stUsers = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE perfil_id = ?');
        $stUsers->execute([$id]);
        $usersCount = (int) $stUsers->fetchColumn();

        if ($usersCount > 0 && $reassignProfileId <= 0) {
            $picked = $this->pickReassignProfile($pdo, $id);
            $reassignProfileId = $picked !== null ? (int) $picked : 0;
        }

        if ($usersCount > 0) {
            if ($reassignProfileId <= 0 || $reassignProfileId === $id) {
                throw new RuntimeException('Nao foi possivel encontrar um perfil de destino para os usuarios vinculados', 422);
            }
            $stTarget = $pdo->prepare('SELECT id FROM perfis_acesso WHERE id = ? LIMIT 1');
            $stTarget->execute([$reassignProfileId]);
            if (!$stTarget->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('Perfil de destino para realocacao dos usuarios nao encontrado', 422);
            }
        }

        $pdo->beginTransaction();
        try {
            $movedUsers = 0;
            if ($usersCount > 0) {
                $upUsers = $pdo->prepare('UPDATE usuarios SET perfil_id = ? WHERE perfil_id = ?');
                $upUsers->execute([$reassignProfileId, $id]);
                $movedUsers = (int) $upUsers->rowCount();
            }

            $pdo->prepare('DELETE FROM perfil_permissoes WHERE perfil_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM perfis_acesso WHERE id = ?')->execute([$id]);
            
            $pdo->commit();
            
            $this->audit->log('permissions', 'delete_profile', 'perfis_acesso', $id, [], [
                'moved_users' => $movedUsers,
                'reassigned_to' => $usersCount > 0 ? $reassignProfileId : null
            ]);

            return [
                'deleted' => true,
                'moved_users' => $movedUsers,
                'reassign_profile_id' => $usersCount > 0 ? $reassignProfileId : null
            ];
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException('Falha ao excluir perfil: ' . $e->getMessage(), 500);
        }
    }

    private function pickReassignProfile(PDO $db, int $excludeProfileId): ?int
    {
        $excludeProfileId = (int) $excludeProfileId;

        $stAssoc = $db->prepare('SELECT id FROM perfis_acesso WHERE LOWER(TRIM(nome)) = ? AND id <> ? LIMIT 1');
        $stAssoc->execute(['associado', $excludeProfileId]);
        $assocId = (int) ($stAssoc->fetchColumn() ?: 0);
        if ($assocId > 0) {
            return $assocId;
        }

        $st = $db->prepare("SELECT p.id
            FROM perfis_acesso p
            LEFT JOIN usuarios u ON u.perfil_id = p.id
            WHERE p.id <> ? AND p.ativo = 1 AND p.id <> 1
            GROUP BY p.id
            ORDER BY COUNT(u.id) ASC, p.id ASC
            LIMIT 1");
        $st->execute([$excludeProfileId]);
        $candidate = (int) ($st->fetchColumn() ?: 0);
        if ($candidate > 0) {
            return $candidate;
        }

        $stAny = $db->prepare("SELECT p.id
            FROM perfis_acesso p
            LEFT JOIN usuarios u ON u.perfil_id = p.id
            WHERE p.id <> ?
            GROUP BY p.id
            ORDER BY COUNT(u.id) ASC, p.id ASC
            LIMIT 1");
        $stAny->execute([$excludeProfileId]);
        $fallback = (int) ($stAny->fetchColumn() ?: 0);

        return $fallback > 0 ? $fallback : null;
    }
}
