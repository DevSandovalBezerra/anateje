<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule\Application;

use Anateje\Contracts\DbConnection;
use PDO;

final class ListPermissions
{
    public function __construct(private DbConnection $db)
    {
    }

    public function execute(): array
    {
        $pdo = $this->db->getPdo();

        $profilesSql = "SELECT p.id, p.nome, p.descricao, p.ativo, p.created_at,
            (SELECT COUNT(*) FROM usuarios u WHERE u.perfil_id = p.id) AS users_count
            FROM perfis_acesso p
            ORDER BY p.id ASC";
        $profiles = $pdo->query($profilesSql)->fetchAll(PDO::FETCH_ASSOC);

        $permissions = $pdo->query('SELECT id, codigo, modulo, nome, ordem, ativo FROM permissoes ORDER BY modulo ASC, ordem ASC, id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);

        $links = $pdo->query('SELECT perfil_id, permissao_id FROM perfil_permissoes WHERE concedida = 1')
            ->fetchAll(PDO::FETCH_ASSOC);

        $matrix = [];
        foreach ($links as $row) {
            $profileId = (int) $row['perfil_id'];
            $permId = (int) $row['permissao_id'];
            if (!isset($matrix[$profileId])) {
                $matrix[$profileId] = [];
            }
            $matrix[$profileId][] = $permId;
        }

        return [
            'profiles' => $profiles,
            'permissions' => $permissions,
            'profile_permissions' => $matrix
        ];
    }
}
