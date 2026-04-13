<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Application;

use Anateje\Contracts\DbConnection;
use PDO;

final class ListMembers
{
    public function __construct(private DbConnection $db)
    {
    }

    public function execute(array $query): array
    {
        $filters = $this->parseFilters($query);
        $pagination = $this->parsePagination($query, 20, 100);
        $orderBy = $this->sortSql($query);

        $pdo = $this->db->getPdo();
        $params = [];
        $where = $this->whereSql($filters, $params);

        $countSql = "SELECT COUNT(*) AS c
            FROM members m
            LEFT JOIN usuarios u ON u.id = m.user_id
            LEFT JOIN addresses a ON a.member_id = m.id
            $where";
        $sc = $pdo->prepare($countSql);
        $sc->execute($params);
        $total = (int) ($sc->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $offset = (int) $pagination['offset'];
        $perPage = (int) $pagination['per_page'];
        $listSql = "SELECT
                m.id, m.user_id, m.nome, m.cpf, m.categoria, m.status, m.lotacao, m.cargo,
                m.telefone, m.email_funcional, m.matricula, m.data_filiacao, m.contribuicao_mensal, m.created_at,
                u.email AS login_email, u.ativo AS user_ativo,
                a.uf, a.cidade
            FROM members m
            LEFT JOIN usuarios u ON u.id = m.user_id
            LEFT JOIN addresses a ON a.member_id = m.id
            $where
            ORDER BY $orderBy
            LIMIT $offset, $perPage";
        $sl = $pdo->prepare($listSql);
        $sl->execute($params);

        return [
            'members' => $sl->fetchAll(PDO::FETCH_ASSOC),
            'filters' => $filters,
            'pagination' => [
                'page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
            ],
            'meta' => [
                'filters' => $filters,
                'pagination' => [
                    'page' => $pagination['page'],
                    'per_page' => $pagination['per_page'],
                    'total' => $total,
                    'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
                ],
            ],
        ];
    }

    private function parsePagination(array $query, int $defaultPerPage, int $maxPerPage): array
    {
        $page = (int) ($query['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = (int) ($query['per_page'] ?? $defaultPerPage);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    private function parseFilters(array $query): array
    {
        $status = strtoupper(trim((string) ($query['status'] ?? '')));
        if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
            $status = '';
        }

        $categoria = strtoupper(trim((string) ($query['categoria'] ?? '')));
        if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
            $categoria = '';
        }

        $uf = strtoupper(trim((string) ($query['uf'] ?? '')));
        if (!preg_match('/^[A-Z]{2}$/', $uf)) {
            $uf = '';
        }

        $q = trim((string) ($query['q'] ?? ''));
        if (strlen($q) > 120) {
            $q = substr($q, 0, 120);
        }

        return [
            'status' => $status,
            'categoria' => $categoria,
            'uf' => $uf,
            'q' => $q,
        ];
    }

    private function sortSql(array $query): string
    {
        $sort = strtolower(trim((string) ($query['sort'] ?? 'id')));
        $order = strtolower(trim((string) ($query['order'] ?? 'desc')));
        $order = $order === 'asc' ? 'ASC' : 'DESC';

        $allowed = [
            'id' => 'm.id',
            'nome' => 'm.nome',
            'categoria' => 'm.categoria',
            'status' => 'm.status',
            'created_at' => 'm.created_at',
            'data_filiacao' => 'm.data_filiacao',
        ];
        $col = $allowed[$sort] ?? 'm.id';

        return $col . ' ' . $order . ', m.id DESC';
    }

    private function whereSql(array $filters, array &$params): string
    {
        $params = [];
        $where = ' WHERE 1=1';

        if (($filters['status'] ?? '') !== '') {
            $where .= ' AND m.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['categoria'] ?? '') !== '') {
            $where .= ' AND m.categoria = ?';
            $params[] = $filters['categoria'];
        }
        if (($filters['uf'] ?? '') !== '') {
            $where .= ' AND a.uf = ?';
            $params[] = $filters['uf'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where .= ' AND (m.nome LIKE ? OR m.cpf LIKE ? OR m.matricula LIKE ? OR m.email_funcional LIKE ? OR u.email LIKE ? OR m.telefone LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return $where;
    }
}

