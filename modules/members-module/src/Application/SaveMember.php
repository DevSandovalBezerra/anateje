<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Application;

use Anateje\Contracts\AuditLogger;
use Anateje\Contracts\AuthContextProvider;
use Anateje\Contracts\DbConnection;
use PDO;
use RuntimeException;
use Throwable;

final class SaveMember
{
    public function __construct(
        private DbConnection $db,
        private AuditLogger $audit,
        private AuthContextProvider $auth,
        private GetMember $getMember
    ) {
    }

    public function execute(array $in): array
    {
        $pdo = $this->db->getPdo();
        $userId = (int) ($this->auth->getUserId() ?? 0);

        $id = (int) ($in['id'] ?? 0);
        $payload = $this->extractPayload($in, true);

        try {
            $existing = null;
            if ($id > 0) {
                $existing = $this->getMember->fetchDetail($pdo, $id);
                if (!$existing) {
                    throw new RuntimeException('MEMBER_NOT_FOUND');
                }
            }

            $this->validateUnique($pdo, $payload['cpf'], $payload['matricula'], $payload['email_funcional'], $id > 0 ? $id : 0);

            $status = $payload['status'];
            $userAtivo = $payload['user_ativo'] ? 1 : 0;
            if ($status === 'INATIVO') {
                $userAtivo = 0;
            }

            $tempPassword = null;
            $senhaHash = null;
            if ($payload['nova_senha'] !== '') {
                $senhaHash = password_hash($payload['nova_senha'], PASSWORD_DEFAULT);
            }

            $pdo->beginTransaction();

            $userIdRef = 0;
            if ($id > 0) {
                $userIdRef = (int) $existing['user_id'];

                $su = $pdo->prepare('SELECT id, email, perfil_id FROM usuarios WHERE id = ? LIMIT 1');
                $su->execute([$userIdRef]);
                $userRow = $su->fetch(PDO::FETCH_ASSOC);
                if (!$userRow) {
                    throw new RuntimeException('USER_NOT_FOUND');
                }
                if ((int) ($userRow['perfil_id'] ?? 0) === 1) {
                    throw new RuntimeException('ADMIN_USER_NOT_ALLOWED');
                }

                $du = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
                $du->execute([$payload['login_email'], $userIdRef]);
                if ($du->fetch(PDO::FETCH_ASSOC)) {
                    throw new RuntimeException('EMAIL_ACESSO_DUPLICADO');
                }

                if ($senhaHash !== null) {
                    $uu = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, ativo = ?, perfil_id = 2, senha = ? WHERE id = ?');
                    $uu->execute([$payload['nome'], $payload['login_email'], $userAtivo, $senhaHash, $userIdRef]);
                } else {
                    $uu = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, ativo = ?, perfil_id = 2 WHERE id = ?');
                    $uu->execute([$payload['nome'], $payload['login_email'], $userAtivo, $userIdRef]);
                }
            } else {
                $su = $pdo->prepare('SELECT id, email, perfil_id FROM usuarios WHERE email = ? LIMIT 1');
                $su->execute([$payload['login_email']]);
                $userRow = $su->fetch(PDO::FETCH_ASSOC);

                if ($userRow) {
                    $candidateUserId = (int) $userRow['id'];
                    $ck = $pdo->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
                    $ck->execute([$candidateUserId]);
                    if ($ck->fetch(PDO::FETCH_ASSOC)) {
                        throw new RuntimeException('USER_ALREADY_LINKED');
                    }
                    if ((int) ($userRow['perfil_id'] ?? 0) === 1) {
                        throw new RuntimeException('ADMIN_USER_NOT_ALLOWED');
                    }

                    $userIdRef = $candidateUserId;
                    if ($senhaHash !== null) {
                        $uu = $pdo->prepare('UPDATE usuarios SET nome = ?, ativo = ?, perfil_id = 2, senha = ? WHERE id = ?');
                        $uu->execute([$payload['nome'], $userAtivo, $senhaHash, $userIdRef]);
                    } else {
                        $uu = $pdo->prepare('UPDATE usuarios SET nome = ?, ativo = ?, perfil_id = 2 WHERE id = ?');
                        $uu->execute([$payload['nome'], $userAtivo, $userIdRef]);
                    }
                } else {
                    if ($senhaHash === null) {
                        $tempPassword = MemberData::generateTempPassword(10);
                        $senhaHash = password_hash($tempPassword, PASSWORD_DEFAULT);
                    }
                    $iu = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, perfil_id, ativo) VALUES (?, ?, ?, 2, ?)');
                    $iu->execute([$payload['nome'], $payload['login_email'], $senhaHash, $userAtivo]);
                    $userIdRef = (int) $pdo->lastInsertId();
                }
            }

            $memberParams = [
                $payload['nome'],
                $payload['lotacao'],
                $payload['cargo'],
                $payload['cpf'],
                $payload['data_filiacao'],
                $payload['categoria'],
                $payload['status'],
                $payload['contribuicao_mensal'],
                $payload['matricula'],
                $payload['telefone'],
                $payload['email_funcional'],
            ];

            if ($id > 0) {
                $st = $pdo->prepare('UPDATE members
                    SET nome=?, lotacao=?, cargo=?, cpf=?, data_filiacao=?, categoria=?, status=?, contribuicao_mensal=?, matricula=?, telefone=?, email_funcional=?
                    WHERE id=?');
                $memberParams[] = $id;
                $st->execute($memberParams);
                $memberId = $id;
            } else {
                $st = $pdo->prepare('INSERT INTO members
                    (nome, lotacao, cargo, cpf, data_filiacao, categoria, status, contribuicao_mensal, matricula, telefone, email_funcional, user_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $memberParams[] = $userIdRef;
                $st->execute($memberParams);
                $memberId = (int) $pdo->lastInsertId();
            }

            $this->upsertAddress($pdo, $memberId, $payload['address']);

            if ($id > 0) {
                $oldStatus = (string) ($existing['status'] ?? '');
                if ($oldStatus !== $payload['status']) {
                    $hist = $pdo->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
                        VALUES (?, ?, ?, ?, ?)');
                    $hist->execute([
                        $memberId,
                        $oldStatus !== '' ? $oldStatus : null,
                        $payload['status'],
                        $userId,
                        $payload['status_reason'] !== '' ? $payload['status_reason'] : null,
                    ]);
                }
            } else {
                $hist = $pdo->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
                    VALUES (?, NULL, ?, ?, ?)');
                $hist->execute([
                    $memberId,
                    $payload['status'],
                    $userId,
                    'Cadastro inicial',
                ]);
            }

            $after = $this->getMember->fetchDetail($pdo, $memberId);

            $this->audit->log(
                'admin.associados',
                $id > 0 ? 'update' : 'create',
                'member',
                $memberId,
                $existing ?? [],
                $after ?? [],
                ['login_email' => $payload['login_email']]
            );

            $pdo->commit();

            $resp = ['id' => $memberId, 'saved' => true];
            if ($tempPassword !== null) {
                $resp['temp_password'] = $tempPassword;
            }

            return ['ok' => true, 'data' => $resp];
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

    private function extractPayload(array $in, bool $adminMode): array
    {
        $nome = trim((string) ($in['nome'] ?? ''));
        if ($nome === '') {
            throw new RuntimeException('VALIDATION:NOME');
        }

        $cpf = MemberData::onlyDigits($in['cpf'] ?? '');
        if (!MemberData::validCpf($cpf)) {
            throw new RuntimeException('VALIDATION:CPF');
        }

        $categoria = strtoupper(trim((string) ($in['categoria'] ?? 'PARCIAL')));
        if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
            $categoria = 'PARCIAL';
        }

        $status = strtoupper(trim((string) ($in['status'] ?? 'ATIVO')));
        if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
            $status = 'ATIVO';
        }

        $dataFiliacao = MemberData::parseDate($in['data_filiacao'] ?? '');
        if (($in['data_filiacao'] ?? '') !== '' && $dataFiliacao === null) {
            throw new RuntimeException('VALIDATION:DATA_FILIACAO');
        }

        $emailFuncional = trim((string) ($in['email_funcional'] ?? ''));
        if ($emailFuncional !== '' && !filter_var($emailFuncional, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('VALIDATION:EMAIL_FUNCIONAL');
        }
        if ($emailFuncional !== '') {
            $emailFuncional = strtolower($emailFuncional);
        }

        $contribuicao = MemberData::parseMoney($in['contribuicao_mensal'] ?? null);
        if (($in['contribuicao_mensal'] ?? null) !== null && ($in['contribuicao_mensal'] ?? '') !== '' && $contribuicao === null) {
            throw new RuntimeException('VALIDATION:CONTRIBUICAO');
        }

        $matricula = trim((string) ($in['matricula'] ?? ''));
        if ($matricula !== '' && strlen($matricula) > 60) {
            throw new RuntimeException('VALIDATION:MATRICULA');
        }

        $telefone = trim((string) ($in['telefone'] ?? ''));
        if ($telefone !== '' && strlen($telefone) > 30) {
            throw new RuntimeException('VALIDATION:TELEFONE');
        }

        $addressRaw = is_array($in['address'] ?? null) ? $in['address'] : [];
        $cep = MemberData::onlyDigits($addressRaw['cep'] ?? '');
        if ($cep !== '' && strlen($cep) !== 8) {
            throw new RuntimeException('VALIDATION:CEP');
        }

        $address = [
            'cep' => $cep !== '' ? $cep : null,
            'logradouro' => trim((string) ($addressRaw['logradouro'] ?? '')) ?: null,
            'numero' => trim((string) ($addressRaw['numero'] ?? '')) ?: null,
            'complemento' => trim((string) ($addressRaw['complemento'] ?? '')) ?: null,
            'bairro' => trim((string) ($addressRaw['bairro'] ?? '')) ?: null,
            'cidade' => trim((string) ($addressRaw['cidade'] ?? '')) ?: null,
            'uf' => strtoupper(trim((string) ($addressRaw['uf'] ?? ''))) ?: null,
        ];

        if ($address['uf'] !== null && !preg_match('/^[A-Z]{2}$/', $address['uf'])) {
            throw new RuntimeException('VALIDATION:UF');
        }

        $payload = [
            'nome' => $nome,
            'lotacao' => trim((string) ($in['lotacao'] ?? '')) ?: null,
            'cargo' => trim((string) ($in['cargo'] ?? '')) ?: null,
            'cpf' => $cpf,
            'data_filiacao' => $dataFiliacao,
            'categoria' => $categoria,
            'status' => $status,
            'contribuicao_mensal' => $contribuicao,
            'matricula' => $matricula !== '' ? $matricula : null,
            'telefone' => $telefone !== '' ? $telefone : null,
            'email_funcional' => $emailFuncional !== '' ? $emailFuncional : null,
            'address' => $address,
        ];

        if ($adminMode) {
            $loginEmail = strtolower(trim((string) ($in['login_email'] ?? '')));
            if ($loginEmail === '' || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('VALIDATION:LOGIN_EMAIL');
            }
            $payload['login_email'] = $loginEmail;
            $payload['nova_senha'] = trim((string) ($in['nova_senha'] ?? ''));
            if ($payload['nova_senha'] !== '' && strlen($payload['nova_senha']) < 8) {
                throw new RuntimeException('VALIDATION:NOVA_SENHA');
            }
            $payload['user_ativo'] = !empty($in['user_ativo']) ? 1 : 0;
            $payload['status_reason'] = trim((string) ($in['status_reason'] ?? ''));
        }

        return $payload;
    }

    private function validateUnique(PDO $db, string $cpf, ?string $matricula, ?string $emailFuncional, int $ignoreId): void
    {
        $stCpf = $db->prepare('SELECT id FROM members WHERE cpf = ? AND id <> ? LIMIT 1');
        $stCpf->execute([$cpf, $ignoreId]);
        if ($stCpf->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('CPF_DUPLICADO');
        }

        if ($matricula !== null && $matricula !== '') {
            $stMat = $db->prepare('SELECT id FROM members WHERE matricula = ? AND id <> ? LIMIT 1');
            $stMat->execute([$matricula, $ignoreId]);
            if ($stMat->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('MATRICULA_DUPLICADA');
            }
        }

        if ($emailFuncional !== null && $emailFuncional !== '') {
            $stMail = $db->prepare('SELECT id FROM members WHERE email_funcional = ? AND id <> ? LIMIT 1');
            $stMail->execute([$emailFuncional, $ignoreId]);
            if ($stMail->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('EMAIL_FUNCIONAL_DUPLICADO');
            }
        }
    }

    private function upsertAddress(PDO $db, int $memberId, array $address): void
    {
        $hasAny = false;
        foreach ($address as $value) {
            if ($value !== null && $value !== '') {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            $db->prepare('DELETE FROM addresses WHERE member_id = ?')->execute([$memberId]);
            return;
        }

        if (($address['cep'] ?? null) === null) {
            throw new RuntimeException('CEP_REQUIRED');
        }

        $sa = $db->prepare('SELECT id FROM addresses WHERE member_id = ? LIMIT 1');
        $sa->execute([$memberId]);
        $exists = $sa->fetch(PDO::FETCH_ASSOC);

        $params = [
            $address['cep'],
            $address['logradouro'],
            $address['numero'],
            $address['complemento'],
            $address['bairro'],
            $address['cidade'],
            $address['uf'],
            $memberId,
        ];

        if ($exists) {
            $ua = $db->prepare('UPDATE addresses
                SET cep=?, logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, uf=?
                WHERE member_id=?');
            $ua->execute($params);
        } else {
            $ia = $db->prepare('INSERT INTO addresses
                (cep, logradouro, numero, complemento, bairro, cidade, uf, member_id)
                VALUES (?,?,?,?,?,?,?,?)');
            $ia->execute($params);
        }
    }
}

