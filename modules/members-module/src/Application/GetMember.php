<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Application;

use Anateje\Contracts\DbConnection;
use PDO;

final class GetMember
{
    public function __construct(private DbConnection $db)
    {
    }

    public function execute(int $id): array
    {
        $pdo = $this->db->getPdo();

        $member = $this->fetchDetail($pdo, $id);
        if ($member === null) {
            return ['found' => false];
        }

        $sh = $pdo->prepare('SELECT id, old_status, new_status, reason, changed_by, created_at
            FROM member_status_history
            WHERE member_id = ?
            ORDER BY id DESC
            LIMIT 20');
        $sh->execute([$id]);

        return [
            'found' => true,
            'member' => $member,
            'status_history' => $sh->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function fetchDetail(PDO $db, int $id): ?array
    {
        $st = $db->prepare("SELECT
                m.*,
                u.email AS login_email,
                u.ativo AS user_ativo,
                a.cep, a.logradouro, a.numero, a.complemento, a.bairro, a.cidade, a.uf
            FROM members m
            LEFT JOIN usuarios u ON u.id = m.user_id
            LEFT JOIN addresses a ON a.member_id = m.id
            WHERE m.id = ?
            LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'nome' => $row['nome'],
            'lotacao' => $row['lotacao'],
            'cargo' => $row['cargo'],
            'cpf' => $row['cpf'],
            'data_filiacao' => $row['data_filiacao'],
            'categoria' => $row['categoria'],
            'status' => $row['status'],
            'contribuicao_mensal' => $row['contribuicao_mensal'],
            'matricula' => $row['matricula'],
            'telefone' => $row['telefone'],
            'email_funcional' => $row['email_funcional'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'login_email' => $row['login_email'],
            'user_ativo' => isset($row['user_ativo']) ? (int) $row['user_ativo'] : 0,
            'address' => [
                'cep' => $row['cep'],
                'logradouro' => $row['logradouro'],
                'numero' => $row['numero'],
                'complemento' => $row['complemento'],
                'bairro' => $row['bairro'],
                'cidade' => $row['cidade'],
                'uf' => $row['uf'],
            ],
        ];
    }
}

