<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\AuthContextProvider;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;
use Throwable;

final class SaveBenefit
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit,
        private AuthContextProvider $auth
    ) {
    }

    public function execute(array $in): array
    {
        $pdo = $this->db->getPdo();
        $actorId = (int) ($this->auth->getUserId() ?? 0);

        $id = (int) ($in['id'] ?? 0);
        $nome = trim((string) ($in['nome'] ?? ''));
        if ($nome === '') {
            return ['ok' => false, 'error_code' => 'VALIDATION', 'error_message' => 'Nome e obrigatorio', 'status' => 422];
        }

        $descricao = trim((string) ($in['descricao'] ?? ''));
        $link = trim((string) ($in['link'] ?? ''));
        $status = ($in['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $sortOrder = (int) ($in['sort_order'] ?? 0);

        $eligibilityCategoria = strtoupper(trim((string) ($in['eligibility_categoria'] ?? 'ALL')));
        if (!in_array($eligibilityCategoria, ['ALL', 'PARCIAL', 'INTEGRAL'], true)) {
            $eligibilityCategoria = 'ALL';
        }

        $eligibilityMemberStatus = strtoupper(trim((string) ($in['eligibility_member_status'] ?? 'ALL')));
        if (!in_array($eligibilityMemberStatus, ['ALL', 'ATIVO', 'INATIVO'], true)) {
            $eligibilityMemberStatus = 'ALL';
        }

        $pdo->beginTransaction();
        try {
            $before = null;
            if ($id > 0) {
                $sb = $pdo->prepare('SELECT * FROM benefits WHERE id = ? LIMIT 1');
                $sb->execute([$id]);
                $before = $sb->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            if ($id > 0) {
                $st = $pdo->prepare('UPDATE benefits
                    SET nome=?, descricao=?, link=?, status=?, eligibility_categoria=?, eligibility_member_status=?, sort_order=?
                    WHERE id=?');
                $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $eligibilityCategoria, $eligibilityMemberStatus, $sortOrder, $id]);
            } else {
                $st = $pdo->prepare('INSERT INTO benefits
                    (nome, descricao, link, status, eligibility_categoria, eligibility_member_status, sort_order)
                    VALUES (?,?,?,?,?,?,?)');
                $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $eligibilityCategoria, $eligibilityMemberStatus, $sortOrder]);
                $id = (int) $pdo->lastInsertId();
            }

            $sa = $pdo->prepare('SELECT * FROM benefits WHERE id = ? LIMIT 1');
            $sa->execute([$id]);
            $after = $sa->fetch(PDO::FETCH_ASSOC) ?: null;

            $this->audit->log(
                'admin.beneficios',
                $before ? 'update' : 'create',
                'benefit',
                $id,
                $before ?? [],
                $after ?? [],
                ['actor_id' => $actorId]
            );

            $pdo->commit();
            return ['ok' => true, 'data' => ['id' => $id]];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Falha ao salvar beneficio', 500);
        }
    }
}

