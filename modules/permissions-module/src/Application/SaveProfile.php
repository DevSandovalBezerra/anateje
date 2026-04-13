<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;

final class SaveProfile
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit
    ) {
    }

    public function execute(int $id, string $nome, string $descricao, bool $ativo): int
    {
        $pdo = $this->db->getPdo();
        
        if ($nome === '') {
            throw new RuntimeException('Nome do perfil é obrigatório', 422);
        }

        $pdo->beginTransaction();
        try {
            $isNew = false;
            if ($id > 0) {
                $st = $pdo->prepare('UPDATE perfis_acesso SET nome = ?, descricao = ?, ativo = ? WHERE id = ?');
                $st->execute([$nome, $descricao !== '' ? $descricao : null, (int) $ativo, $id]);
            } else {
                $isNew = true;
                $st = $pdo->prepare('INSERT INTO perfis_acesso (nome, descricao, ativo) VALUES (?, ?, ?)');
                $st->execute([$nome, $descricao !== '' ? $descricao : null, (int) $ativo]);
                $id = (int) $pdo->lastInsertId();
            }

            $this->syncProfileJson($pdo, $id);
            $pdo->commit();
            
            $this->audit->log('permissions', $isNew ? 'create_profile' : 'update_profile', 'perfis_acesso', $id, [], [
                'nome' => $nome,
                'ativo' => $ativo
            ]);
            
            return $id;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException('Falha ao salvar perfil: ' . $e->getMessage(), 500);
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
