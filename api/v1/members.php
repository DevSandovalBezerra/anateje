<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';
$userId = (int) $auth['sub'];

if ($action === 'get') {
    $st = $db->prepare('SELECT * FROM members WHERE user_id = ? LIMIT 1');
    $st->execute([$userId]);
    $member = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    $address = null;
    if ($member) {
        $sa = $db->prepare('SELECT * FROM addresses WHERE member_id = ? LIMIT 1');
        $sa->execute([(int) $member['id']]);
        $address = $sa->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    anateje_ok([
        'member' => $member,
        'address' => $address,
        'auth' => [
            'user_id' => $auth['sub'],
            'is_admin' => $auth['is_admin']
        ]
    ]);
}

if ($action === 'update') {
    anateje_require_method(['POST']);

    $in = anateje_input();

    $nome = trim((string) ($in['nome'] ?? ''));
    if ($nome === '') {
        anateje_error('VALIDATION', 'Nome e obrigatorio', 422);
    }

    $cpf = anateje_only_digits($in['cpf'] ?? '');
    if (!anateje_valid_cpf($cpf)) {
        anateje_error('VALIDATION', 'CPF invalido', 422);
    }

    $categoria = strtoupper(trim((string) ($in['categoria'] ?? 'PARCIAL')));
    if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
        $categoria = 'PARCIAL';
    }

    $status = strtoupper(trim((string) ($in['status'] ?? 'ATIVO')));
    if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
        $status = 'ATIVO';
    }

    $emailFuncional = trim((string) ($in['email_funcional'] ?? ''));
    if ($emailFuncional !== '' && !filter_var($emailFuncional, FILTER_VALIDATE_EMAIL)) {
        anateje_error('VALIDATION', 'Email funcional invalido', 422);
    }

    $dataFiliacao = trim((string) ($in['data_filiacao'] ?? ''));
    if ($dataFiliacao !== '') {
        $dt = date_create($dataFiliacao);
        if (!$dt) {
            anateje_error('VALIDATION', 'Data de filiacao invalida', 422);
        }
        $dataFiliacao = $dt->format('Y-m-d');
    } else {
        $dataFiliacao = null;
    }

    $contribuicao = $in['contribuicao_mensal'] ?? null;
    if ($contribuicao === '' || $contribuicao === null) {
        $contribuicao = null;
    } elseif (!is_numeric($contribuicao)) {
        anateje_error('VALIDATION', 'Contribuicao mensal invalida', 422);
    } else {
        $contribuicao = (float) $contribuicao;
    }

    $db->beginTransaction();

    try {
        $dup = $db->prepare('SELECT id FROM members WHERE cpf = ? AND user_id <> ? LIMIT 1');
        $dup->execute([$cpf, $userId]);
        if ($dup->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('CPF_DUPLICADO');
        }

        $sm = $db->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
        $sm->execute([$userId]);
        $current = $sm->fetch(PDO::FETCH_ASSOC);

        $payload = [
            $nome,
            trim((string) ($in['lotacao'] ?? '')) ?: null,
            trim((string) ($in['cargo'] ?? '')) ?: null,
            $cpf,
            $dataFiliacao,
            $categoria,
            $status,
            $contribuicao,
            trim((string) ($in['matricula'] ?? '')) ?: null,
            trim((string) ($in['telefone'] ?? '')) ?: null,
            $emailFuncional ?: null,
        ];

        if ($current) {
            $memberId = (int) $current['id'];
            $st = $db->prepare('UPDATE members
                SET nome=?, lotacao=?, cargo=?, cpf=?, data_filiacao=?, categoria=?, status=?, contribuicao_mensal=?, matricula=?, telefone=?, email_funcional=?
                WHERE user_id=?');
            $payload[] = $userId;
            $st->execute($payload);
        } else {
            $st = $db->prepare('INSERT INTO members
                (nome, lotacao, cargo, cpf, data_filiacao, categoria, status, contribuicao_mensal, matricula, telefone, email_funcional, user_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $payload[] = $userId;
            $st->execute($payload);
            $memberId = (int) $db->lastInsertId();
        }

        $address = is_array($in['address'] ?? null) ? $in['address'] : [];
        $cep = anateje_only_digits($address['cep'] ?? '');

        if ($cep !== '') {
            if (strlen($cep) !== 8) {
                throw new RuntimeException('CEP_INVALIDO');
            }

            $addrPayload = [
                $cep,
                trim((string) ($address['logradouro'] ?? '')) ?: null,
                trim((string) ($address['numero'] ?? '')) ?: null,
                trim((string) ($address['complemento'] ?? '')) ?: null,
                trim((string) ($address['bairro'] ?? '')) ?: null,
                trim((string) ($address['cidade'] ?? '')) ?: null,
                strtoupper(trim((string) ($address['uf'] ?? ''))) ?: null,
                $memberId
            ];

            $sa = $db->prepare('SELECT id FROM addresses WHERE member_id = ? LIMIT 1');
            $sa->execute([$memberId]);
            $hasAddress = $sa->fetch(PDO::FETCH_ASSOC);

            if ($hasAddress) {
                $ua = $db->prepare('UPDATE addresses
                    SET cep=?, logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, uf=?
                    WHERE member_id=?');
                $ua->execute($addrPayload);
            } else {
                $ia = $db->prepare('INSERT INTO addresses
                    (cep, logradouro, numero, complemento, bairro, cidade, uf, member_id)
                    VALUES (?,?,?,?,?,?,?,?)');
                $ia->execute($addrPayload);
            }
        }

        $db->commit();
        anateje_ok(['member_id' => $memberId]);
    } catch (RuntimeException $e) {
        $db->rollBack();

        $code = $e->getMessage();
        if ($code === 'CPF_DUPLICADO') {
            anateje_error('CPF_DUPLICADO', 'CPF ja cadastrado', 422);
        }
        if ($code === 'CEP_INVALIDO') {
            anateje_error('CEP_INVALIDO', 'CEP invalido', 422);
        }

        anateje_error('FAIL', 'Falha ao atualizar perfil', 500);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro members.update: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar perfil', 500);
    }
}

if ($action === 'admin_list') {
    anateje_require_admin($auth);

    $rows = $db->query('SELECT id, user_id, nome, cpf, categoria, status, lotacao, cargo, telefone, email_funcional, created_at FROM members ORDER BY id DESC')
        ->fetchAll(PDO::FETCH_ASSOC);

    anateje_ok(['members' => $rows]);
}

if ($action === 'admin_save_status') {
    anateje_require_admin($auth);
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);
    $status = strtoupper(trim((string) ($in['status'] ?? 'ATIVO')));

    if ($id <= 0 || !in_array($status, ['ATIVO', 'INATIVO'], true)) {
        anateje_error('VALIDATION', 'Dados invalidos para atualizar status', 422);
    }

    $st = $db->prepare('UPDATE members SET status = ? WHERE id = ?');
    $st->execute([$status, $id]);

    anateje_ok(['updated' => true]);
}

if ($action === 'admin_delete') {
    anateje_require_admin($auth);

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM addresses WHERE member_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM member_benefits WHERE member_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM event_registrations WHERE member_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM members WHERE id = ?')->execute([$id]);
        $db->commit();

        anateje_ok(['deleted' => true]);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro members.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir associado', 500);
    }
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
