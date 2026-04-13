<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;

final class SaveProfilePermissions
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit
    ) {
    }

    public function execute(int $profileId, array $permissionIds): void
    {
        $pdo = $this->db->getPdo();

        if ($profileId <= 0) {
            throw new RuntimeException('Perfil invalido', 422);
        }

        $stProfile = $pdo->prepare('SELECT id FROM perfis_acesso WHERE id = ? LIMIT 1');
        $stProfile->execute([$profileId]);
        if (!$stProfile->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Perfil não encontrado', 404);
        }

        $normalized = [];
        foreach ($permissionIds as $pid) {
            $pid = (int) $pid;
            if ($pid > 0 && !in_array($pid, $normalized, true)) {
                $normalized[] = $pid;
            }
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM perfil_permissoes WHERE perfil_id = ?')->execute([$profileId]);
            if (!empty($normalized)) {
                $ins = $pdo->prepare('INSERT INTO perfil_permissoes (perfil_id, permissao_id, concedida) VALUES (?, ?, 1)');
                foreach ($normalized as $pid) {
                    $ins->execute([$profileId, $pid]);
                }
            }

            $this->syncProfileJson($pdo, $profileId);
            $pdo->commit();
            
            $this->audit->log('permissions', 'update_profile_permissions', 'perfis_acesso', $profileId, [], [
                'permissions' => $normalized
            ]);
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException('Falha ao salvar permissoes do perfil: ' . $e->getMessage(), 500);
        }
    }

    private function syncProfileJson(PDO $db, int $profileId): void
    {
        $st = $db->prepare("SELECT p.modulo, p.codigo
            FROM perfil_permissoes pp
            INNER JOIN permissoes p ON p.id = pp.permissao_id
            WHERE pp.perfil_id = ? AND pp.concedida = 1 AND p.ativo = 1
            ORDER BY p.modulo, p.ordem, p.id");
        $st->execute([$profileId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $mod = (string) ($row['modulo'] ?? '');
            $code = (string) ($row['codigo'] ?? '');
            if ($mod === '' || $code === '') {
                continue;
            }
            if (substr_count($code, '.') !== 1) {
                continue;
            }
            if (!isset($map[$mod])) {
                $map[$mod] = [];
            }
            $parts = explode('.', $code, 2);
            $page = $parts[1] ?? $code;
            if (!in_array($page, $map[$mod], true)) {
                $map[$mod][] = $page;
            }
        }

        $json = json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $up = $db->prepare('UPDATE perfis_acesso SET permissoes = ? WHERE id = ?');
        $up->execute([$json ?: '{}', $profileId]);
    }
}
