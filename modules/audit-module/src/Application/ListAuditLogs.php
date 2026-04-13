<?php

declare(strict_types=1);

namespace Anateje\AuditModule\Application;

use Anateje\Contracts\DbConnection;

final class ListAuditLogs
{
    public function __construct(private DbConnection $db)
    {
    }

    public function execute(array $filters, array $pagination): array
    {
        $pdo = $this->db->getPdo();
        $params = [];
        $where = $this->buildWhere($filters, $params);

        $countSql = "SELECT COUNT(*) AS c FROM audit_logs al LEFT JOIN usuarios u ON u.id = al.user_id $where";
        $sc = $pdo->prepare($countSql);
        $sc->execute($params);
        $total = (int) ($sc->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);

        $page = max(1, (int) ($pagination['page'] ?? 1));
        $perPage = max(5, min(200, (int) ($pagination['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;

        $listSql = "SELECT
                al.id, al.user_id, al.modulo, al.acao, al.entidade, al.entidade_id,
                al.ip, al.user_agent, al.created_at,
                u.nome AS user_nome, u.email AS user_email
            FROM audit_logs al
            LEFT JOIN usuarios u ON u.id = al.user_id
            $where
            ORDER BY al.id DESC
            LIMIT $offset, $perPage";
            
        $sl = $pdo->prepare($listSql);
        $sl->execute($params);
        $logs = $sl->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
            ],
            'meta' => [
                'filters' => $filters,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
                ]
            ]
        ];
    }

    private function buildWhere(array $filters, array &$params): string
    {
        $where = ' WHERE 1=1';

        if (($filters['module'] ?? '') !== '') {
            $where .= ' AND al.modulo = ?';
            $params[] = $filters['module'];
        }
        if (($filters['operation'] ?? '') !== '') {
            $where .= ' AND al.acao = ?';
            $params[] = $filters['operation'];
        }
        if (($filters['entity'] ?? '') !== '') {
            $where .= ' AND al.entidade = ?';
            $params[] = $filters['entity'];
        }
        if ((int) ($filters['user_id'] ?? 0) > 0) {
            $where .= ' AND al.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where .= ' AND al.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where .= ' AND al.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (($filters['q'] ?? '') !== '') {
            $where .= ' AND (
                al.modulo LIKE ?
                OR al.acao LIKE ?
                OR al.entidade LIKE ?
                OR CAST(al.entidade_id AS CHAR) LIKE ?
                OR u.nome LIKE ?
                OR u.email LIKE ?
                OR al.ip LIKE ?
            )';
            $like = '%' . $filters['q'] . '%';
            $params = array_merge($params, array_fill(0, 7, $like));
        }

        return $where;
    }
}
