<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';
$userId = (int) $auth['sub'];

function members_csv(string $filename, array $header, array $rows): void
{
    if (ob_get_level() > 0) {
        ob_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header, ';');
    foreach ($rows as $line) {
        fputcsv($out, $line, ';');
    }
    fclose($out);
    exit;
}

function members_parse_pagination(int $defaultPerPage = 20, int $maxPerPage = 100): array
{
    $page = (int) ($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = (int) ($_GET['per_page'] ?? $defaultPerPage);
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

function members_parse_filters(): array
{
    $status = strtoupper(trim((string) ($_GET['status'] ?? '')));
    if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
        $status = '';
    }

    $categoria = strtoupper(trim((string) ($_GET['categoria'] ?? '')));
    if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
        $categoria = '';
    }

    $uf = strtoupper(trim((string) ($_GET['uf'] ?? '')));
    if (!preg_match('/^[A-Z]{2}$/', $uf)) {
        $uf = '';
    }

    $q = trim((string) ($_GET['q'] ?? ''));
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

function members_sort_sql(): string
{
    $sort = strtolower(trim((string) ($_GET['sort'] ?? 'id')));
    $order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
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

function members_where_sql(array $filters, array &$params): string
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

function members_parse_date(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $dt = date_create($value);
    if (!$dt) {
        return null;
    }

    return $dt->format('Y-m-d');
}

function members_parse_money($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    $text = trim((string) $value);
    if ($text === '') {
        return null;
    }

    $text = str_replace('.', '', $text);
    $text = str_replace(',', '.', $text);
    if (!is_numeric($text)) {
        return null;
    }

    return (float) $text;
}

function members_generate_temp_password(int $length = 10): string
{
    $length = max(8, min($length, 20));
    $bytes = bin2hex(random_bytes((int) ceil($length / 2)));
    return substr($bytes, 0, $length);
}

function members_fetch_detail(PDO $db, int $id): ?array
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

function members_validate_unique(PDO $db, string $cpf, ?string $matricula, ?string $emailFuncional, int $ignoreId): void
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

function members_extract_payload(array $in, bool $adminMode = false): array
{
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

    $dataFiliacao = members_parse_date($in['data_filiacao'] ?? '');
    if (($in['data_filiacao'] ?? '') !== '' && $dataFiliacao === null) {
        anateje_error('VALIDATION', 'Data de filiacao invalida', 422);
    }

    $emailFuncional = trim((string) ($in['email_funcional'] ?? ''));
    if ($emailFuncional !== '' && !filter_var($emailFuncional, FILTER_VALIDATE_EMAIL)) {
        anateje_error('VALIDATION', 'Email funcional invalido', 422);
    }
    if ($emailFuncional !== '') {
        $emailFuncional = strtolower($emailFuncional);
    }

    $contribuicao = members_parse_money($in['contribuicao_mensal'] ?? null);
    if (($in['contribuicao_mensal'] ?? null) !== null && ($in['contribuicao_mensal'] ?? '') !== '' && $contribuicao === null) {
        anateje_error('VALIDATION', 'Contribuicao mensal invalida', 422);
    }

    $matricula = trim((string) ($in['matricula'] ?? ''));
    if ($matricula !== '' && strlen($matricula) > 60) {
        anateje_error('VALIDATION', 'Registro associativo invalido', 422);
    }

    $telefone = trim((string) ($in['telefone'] ?? ''));
    if ($telefone !== '' && strlen($telefone) > 30) {
        anateje_error('VALIDATION', 'Telefone invalido', 422);
    }

    $addressRaw = is_array($in['address'] ?? null) ? $in['address'] : [];
    $cep = anateje_only_digits($addressRaw['cep'] ?? '');
    if ($cep !== '' && strlen($cep) !== 8) {
        anateje_error('CEP_INVALIDO', 'CEP invalido', 422);
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
        anateje_error('VALIDATION', 'UF invalida', 422);
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
            anateje_error('VALIDATION', 'Email de acesso invalido', 422);
        }
        $payload['login_email'] = $loginEmail;
        $payload['nova_senha'] = trim((string) ($in['nova_senha'] ?? ''));
        if ($payload['nova_senha'] !== '' && strlen($payload['nova_senha']) < PASSWORD_MIN_LENGTH) {
            anateje_error('VALIDATION', 'Nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres', 422);
        }
        $payload['user_ativo'] = !empty($in['user_ativo']) ? 1 : 0;
        $payload['status_reason'] = trim((string) ($in['status_reason'] ?? ''));
    }

    return $payload;
}

function members_upsert_address(PDO $db, int $memberId, array $address): void
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

function members_import_normalize_column(string $name): string
{
    $name = trim(str_replace("\xEF\xBB\xBF", '', $name));
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9_]+/', '_', $name);
    $name = trim((string) $name, '_');
    return $name;
}

function members_import_detect_delimiter(string $line): string
{
    $semi = substr_count($line, ';');
    $comma = substr_count($line, ',');
    return $semi >= $comma ? ';' : ',';
}

function members_import_parse_csv_text(string $csvText, int $maxRows = 3000): array
{
    $csvText = str_replace("\xEF\xBB\xBF", '', (string) $csvText);
    $csvText = trim($csvText);
    if ($csvText === '') {
        return ['ok' => false, 'error' => 'CSV vazio'];
    }

    $lines = preg_split('/\r\n|\n|\r/', $csvText) ?: [];
    $lines = array_values(array_filter($lines, function ($line) {
        return trim((string) $line) !== '';
    }));
    if (count($lines) < 2) {
        return ['ok' => false, 'error' => 'CSV precisa de cabecalho e ao menos 1 linha de dados'];
    }

    $headerLine = (string) $lines[0];
    $delimiter = members_import_detect_delimiter($headerLine);
    $headerRaw = str_getcsv($headerLine, $delimiter);
    if (!is_array($headerRaw) || empty($headerRaw)) {
        return ['ok' => false, 'error' => 'Cabecalho CSV invalido'];
    }

    $header = array_map(function ($col) {
        return members_import_normalize_column((string) $col);
    }, $headerRaw);

    $required = ['nome', 'cpf', 'login_email'];
    foreach ($required as $req) {
        if (!in_array($req, $header, true)) {
            return ['ok' => false, 'error' => 'Coluna obrigatoria ausente: ' . $req];
        }
    }

    $rows = [];
    $total = 0;
    for ($i = 1; $i < count($lines); $i++) {
        $total++;
        if ($total > $maxRows) {
            return ['ok' => false, 'error' => 'CSV excede limite de ' . $maxRows . ' linhas'];
        }

        $vals = str_getcsv((string) $lines[$i], $delimiter);
        if (!is_array($vals)) {
            continue;
        }

        $row = [];
        foreach ($header as $idx => $col) {
            $row[$col] = trim((string) ($vals[$idx] ?? ''));
        }
        $row['__line'] = $i + 1;
        $rows[] = $row;
    }

    return [
        'ok' => true,
        'delimiter' => $delimiter,
        'header' => $header,
        'rows' => $rows
    ];
}

function members_import_build_payload(array $row): array
{
    $errors = [];

    $nome = trim((string) ($row['nome'] ?? ''));
    if ($nome === '') {
        $errors[] = 'Nome obrigatorio';
    }

    $cpf = anateje_only_digits((string) ($row['cpf'] ?? ''));
    if (!anateje_valid_cpf($cpf)) {
        $errors[] = 'CPF invalido';
    }

    $loginEmail = strtolower(trim((string) ($row['login_email'] ?? '')));
    if ($loginEmail === '' || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email de acesso invalido';
    }

    $categoria = strtoupper(trim((string) ($row['categoria'] ?? 'PARCIAL')));
    if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
        $categoria = 'PARCIAL';
    }

    $status = strtoupper(trim((string) ($row['status'] ?? 'ATIVO')));
    if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
        $status = 'ATIVO';
    }

    $dataFiliacaoRaw = trim((string) ($row['data_filiacao'] ?? ''));
    $dataFiliacao = members_parse_date($dataFiliacaoRaw);
    if ($dataFiliacaoRaw !== '' && $dataFiliacao === null) {
        $errors[] = 'Data de filiacao invalida';
    }

    $emailFuncional = strtolower(trim((string) ($row['email_funcional'] ?? '')));
    if ($emailFuncional !== '' && !filter_var($emailFuncional, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email funcional invalido';
    }

    $uf = strtoupper(trim((string) ($row['uf'] ?? '')));
    if ($uf !== '' && !preg_match('/^[A-Z]{2}$/', $uf)) {
        $errors[] = 'UF invalida';
    }

    $cep = anateje_only_digits((string) ($row['cep'] ?? ''));
    if ($cep !== '' && strlen($cep) !== 8) {
        $errors[] = 'CEP invalido';
    }

    $contribRaw = trim((string) ($row['contribuicao_mensal'] ?? ''));
    $contrib = members_parse_money($contribRaw);
    if ($contribRaw !== '' && $contrib === null) {
        $errors[] = 'Contribuicao mensal invalida';
    }

    $address = [
        'cep' => $cep !== '' ? $cep : null,
        'logradouro' => trim((string) ($row['logradouro'] ?? '')) ?: null,
        'numero' => trim((string) ($row['numero'] ?? '')) ?: null,
        'complemento' => trim((string) ($row['complemento'] ?? '')) ?: null,
        'bairro' => trim((string) ($row['bairro'] ?? '')) ?: null,
        'cidade' => trim((string) ($row['cidade'] ?? '')) ?: null,
        'uf' => $uf !== '' ? $uf : null,
    ];
    $hasAddressData = false;
    foreach ($address as $v) {
        if ($v !== null && $v !== '') {
            $hasAddressData = true;
            break;
        }
    }
    if ($hasAddressData && $address['cep'] === null) {
        $errors[] = 'CEP obrigatorio quando houver endereco';
    }

    return [
        'errors' => $errors,
        'payload' => [
            'nome' => $nome,
            'cpf' => $cpf,
            'matricula' => trim((string) ($row['matricula'] ?? '')) ?: null,
            'login_email' => $loginEmail,
            'categoria' => $categoria,
            'status' => $status,
            'data_filiacao' => $dataFiliacao,
            'cargo' => trim((string) ($row['cargo'] ?? '')) ?: null,
            'lotacao' => trim((string) ($row['lotacao'] ?? '')) ?: null,
            'telefone' => anateje_only_digits((string) ($row['telefone'] ?? '')) ?: null,
            'email_funcional' => $emailFuncional !== '' ? $emailFuncional : null,
            'contribuicao_mensal' => $contrib,
            'user_ativo' => $status === 'ATIVO' ? 1 : 0,
            'address' => $address,
        ],
    ];
}

function members_import_lookup_member_by_cpf(PDO $db, string $cpf): ?array
{
    $st = $db->prepare('SELECT id, user_id FROM members WHERE cpf = ? LIMIT 1');
    $st->execute([$cpf]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function members_import_lookup_user_by_email(PDO $db, string $email): ?array
{
    $st = $db->prepare("SELECT u.id, u.perfil_id, m.id AS member_id
        FROM usuarios u
        LEFT JOIN members m ON m.user_id = u.id
        WHERE u.email = ?
        LIMIT 1");
    $st->execute([$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function members_import_lookup_user_by_id(PDO $db, int $userId): ?array
{
    $st = $db->prepare('SELECT id, perfil_id, email FROM usuarios WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function members_import_db_conflicts(PDO $db, array $payload, int $ignoreMemberId = 0): array
{
    $errors = [];

    $cpf = (string) ($payload['cpf'] ?? '');
    if ($cpf !== '') {
        $stCpf = $db->prepare('SELECT id FROM members WHERE cpf = ? AND id <> ? LIMIT 1');
        $stCpf->execute([$cpf, $ignoreMemberId]);
        if ($stCpf->fetch(PDO::FETCH_ASSOC)) {
            $errors[] = 'CPF ja cadastrado';
        }
    }

    $mat = trim((string) ($payload['matricula'] ?? ''));
    if ($mat !== '') {
        $stMat = $db->prepare('SELECT id FROM members WHERE matricula = ? AND id <> ? LIMIT 1');
        $stMat->execute([$mat, $ignoreMemberId]);
        if ($stMat->fetch(PDO::FETCH_ASSOC)) {
            $errors[] = 'Registro associativo ja cadastrado';
        }
    }

    $mail = strtolower(trim((string) ($payload['email_funcional'] ?? '')));
    if ($mail !== '') {
        $stMail = $db->prepare('SELECT id FROM members WHERE email_funcional = ? AND id <> ? LIMIT 1');
        $stMail->execute([$mail, $ignoreMemberId]);
        if ($stMail->fetch(PDO::FETCH_ASSOC)) {
            $errors[] = 'Email funcional ja cadastrado';
        }
    }

    return $errors;
}

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
            'is_admin' => $auth['is_admin'],
        ],
    ]);
}

if ($action === 'update') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $payload = members_extract_payload($in, false);

    try {
        $sm = $db->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
        $sm->execute([$userId]);
        $current = $sm->fetch(PDO::FETCH_ASSOC);
        $memberId = $current ? (int) $current['id'] : 0;

        members_validate_unique($db, $payload['cpf'], $payload['matricula'], $payload['email_funcional'], $memberId);
        $db->beginTransaction();

        $params = [
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

        if ($current) {
            $st = $db->prepare('UPDATE members
                SET nome=?, lotacao=?, cargo=?, cpf=?, data_filiacao=?, categoria=?, status=?, contribuicao_mensal=?, matricula=?, telefone=?, email_funcional=?
                WHERE user_id=?');
            $params[] = $userId;
            $st->execute($params);
        } else {
            $st = $db->prepare('INSERT INTO members
                (nome, lotacao, cargo, cpf, data_filiacao, categoria, status, contribuicao_mensal, matricula, telefone, email_funcional, user_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $params[] = $userId;
            $st->execute($params);
            $memberId = (int) $db->lastInsertId();
        }

        members_upsert_address($db, $memberId, $payload['address']);
        $db->commit();
        anateje_ok(['member_id' => $memberId]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $code = $e->getMessage();
        if ($code === 'CPF_DUPLICADO') {
            anateje_error('CPF_DUPLICADO', 'CPF ja cadastrado', 422);
        }
        if ($code === 'MATRICULA_DUPLICADA') {
            anateje_error('MATRICULA_DUPLICADA', 'Registro associativo ja cadastrado', 422);
        }
        if ($code === 'EMAIL_FUNCIONAL_DUPLICADO') {
            anateje_error('EMAIL_FUNCIONAL_DUPLICADO', 'Email funcional ja cadastrado', 422);
        }
        if ($code === 'CEP_REQUIRED') {
            anateje_error('VALIDATION', 'CEP e obrigatorio quando houver endereco', 422);
        }
        anateje_error('FAIL', 'Falha ao atualizar perfil', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro members.update: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar perfil', 500);
    }
}

if ($action === 'admin_list') {
    anateje_require_permission($db, $auth, 'admin.associados.view');

    $filters = members_parse_filters();
    $pagination = members_parse_pagination(20, 100);
    $orderBy = members_sort_sql();

    $params = [];
    $where = members_where_sql($filters, $params);

    $countSql = "SELECT COUNT(*) AS c
        FROM members m
        LEFT JOIN usuarios u ON u.id = m.user_id
        LEFT JOIN addresses a ON a.member_id = m.id
        $where";
    $sc = $db->prepare($countSql);
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
    $sl = $db->prepare($listSql);
    $sl->execute($params);

    anateje_ok([
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
            ]
        ],
    ]);
}

if ($action === 'admin_get') {
    anateje_require_permission($db, $auth, 'admin.associados.view');

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $member = members_fetch_detail($db, $id);
    if (!$member) {
        anateje_error('NOT_FOUND', 'Associado nao encontrado', 404);
    }

    $sh = $db->prepare('SELECT id, old_status, new_status, reason, changed_by, created_at
        FROM member_status_history
        WHERE member_id = ?
        ORDER BY id DESC
        LIMIT 20');
    $sh->execute([$id]);

    anateje_ok([
        'member' => $member,
        'status_history' => $sh->fetchAll(PDO::FETCH_ASSOC),
    ]);
}

if ($action === 'admin_save') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);
    anateje_require_permission($db, $auth, $id > 0 ? 'admin.associados.edit' : 'admin.associados.create');
    $payload = members_extract_payload($in, true);

    try {
        $existing = null;
        if ($id > 0) {
            $existing = members_fetch_detail($db, $id);
            if (!$existing) {
                throw new RuntimeException('MEMBER_NOT_FOUND');
            }
        }

        members_validate_unique(
            $db,
            $payload['cpf'],
            $payload['matricula'],
            $payload['email_funcional'],
            $id > 0 ? $id : 0
        );

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

        $db->beginTransaction();

        $userIdRef = 0;
        if ($id > 0) {
            $userIdRef = (int) $existing['user_id'];

            $su = $db->prepare('SELECT id, email, perfil_id FROM usuarios WHERE id = ? LIMIT 1');
            $su->execute([$userIdRef]);
            $userRow = $su->fetch(PDO::FETCH_ASSOC);
            if (!$userRow) {
                throw new RuntimeException('USER_NOT_FOUND');
            }
            if ((int) ($userRow['perfil_id'] ?? 0) === 1) {
                throw new RuntimeException('ADMIN_USER_NOT_ALLOWED');
            }

            $du = $db->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
            $du->execute([$payload['login_email'], $userIdRef]);
            if ($du->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('EMAIL_ACESSO_DUPLICADO');
            }

            if ($senhaHash !== null) {
                $uu = $db->prepare('UPDATE usuarios SET nome = ?, email = ?, ativo = ?, perfil_id = 2, senha = ? WHERE id = ?');
                $uu->execute([$payload['nome'], $payload['login_email'], $userAtivo, $senhaHash, $userIdRef]);
            } else {
                $uu = $db->prepare('UPDATE usuarios SET nome = ?, email = ?, ativo = ?, perfil_id = 2 WHERE id = ?');
                $uu->execute([$payload['nome'], $payload['login_email'], $userAtivo, $userIdRef]);
            }
        } else {
            $su = $db->prepare('SELECT id, email, perfil_id FROM usuarios WHERE email = ? LIMIT 1');
            $su->execute([$payload['login_email']]);
            $userRow = $su->fetch(PDO::FETCH_ASSOC);

            if ($userRow) {
                $candidateUserId = (int) $userRow['id'];
                $ck = $db->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
                $ck->execute([$candidateUserId]);
                if ($ck->fetch(PDO::FETCH_ASSOC)) {
                    throw new RuntimeException('USER_ALREADY_LINKED');
                }
                if ((int) ($userRow['perfil_id'] ?? 0) === 1) {
                    throw new RuntimeException('ADMIN_USER_NOT_ALLOWED');
                }

                $userIdRef = $candidateUserId;
                if ($senhaHash !== null) {
                    $uu = $db->prepare('UPDATE usuarios SET nome = ?, ativo = ?, perfil_id = 2, senha = ? WHERE id = ?');
                    $uu->execute([$payload['nome'], $userAtivo, $senhaHash, $userIdRef]);
                } else {
                    $uu = $db->prepare('UPDATE usuarios SET nome = ?, ativo = ?, perfil_id = 2 WHERE id = ?');
                    $uu->execute([$payload['nome'], $userAtivo, $userIdRef]);
                }
            } else {
                if ($senhaHash === null) {
                    $tempPassword = members_generate_temp_password(10);
                    $senhaHash = password_hash($tempPassword, PASSWORD_DEFAULT);
                }
                $iu = $db->prepare('INSERT INTO usuarios (nome, email, senha, perfil_id, ativo) VALUES (?, ?, ?, 2, ?)');
                $iu->execute([$payload['nome'], $payload['login_email'], $senhaHash, $userAtivo]);
                $userIdRef = (int) $db->lastInsertId();
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
            $st = $db->prepare('UPDATE members
                SET nome=?, lotacao=?, cargo=?, cpf=?, data_filiacao=?, categoria=?, status=?, contribuicao_mensal=?, matricula=?, telefone=?, email_funcional=?
                WHERE id=?');
            $memberParams[] = $id;
            $st->execute($memberParams);
            $memberId = $id;
        } else {
            $st = $db->prepare('INSERT INTO members
                (nome, lotacao, cargo, cpf, data_filiacao, categoria, status, contribuicao_mensal, matricula, telefone, email_funcional, user_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $memberParams[] = $userIdRef;
            $st->execute($memberParams);
            $memberId = (int) $db->lastInsertId();
        }

        members_upsert_address($db, $memberId, $payload['address']);

        if ($id > 0) {
            $oldStatus = (string) ($existing['status'] ?? '');
            if ($oldStatus !== $payload['status']) {
                $hist = $db->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
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
            $hist = $db->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
                VALUES (?, NULL, ?, ?, ?)');
            $hist->execute([
                $memberId,
                $payload['status'],
                $userId,
                'Cadastro inicial',
            ]);
        }

        $after = members_fetch_detail($db, $memberId);
        anateje_audit_log(
            $db,
            $userId,
            'admin.associados',
            $id > 0 ? 'update' : 'create',
            'member',
            $memberId,
            $existing,
            $after,
            ['login_email' => $payload['login_email']]
        );

        $db->commit();

        $resp = ['id' => $memberId, 'saved' => true];
        if ($tempPassword !== null) {
            $resp['temp_password'] = $tempPassword;
        }
        anateje_ok($resp);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $code = $e->getMessage();
        if ($code === 'MEMBER_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Associado nao encontrado', 404);
        }
        if ($code === 'USER_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Usuario vinculado ao associado nao encontrado', 404);
        }
        if ($code === 'CPF_DUPLICADO') {
            anateje_error('CPF_DUPLICADO', 'CPF ja cadastrado', 422);
        }
        if ($code === 'MATRICULA_DUPLICADA') {
            anateje_error('MATRICULA_DUPLICADA', 'Registro associativo ja cadastrado', 422);
        }
        if ($code === 'EMAIL_FUNCIONAL_DUPLICADO') {
            anateje_error('EMAIL_FUNCIONAL_DUPLICADO', 'Email funcional ja cadastrado', 422);
        }
        if ($code === 'EMAIL_ACESSO_DUPLICADO') {
            anateje_error('EMAIL_ACESSO_DUPLICADO', 'Email de acesso ja utilizado por outro usuario', 422);
        }
        if ($code === 'USER_ALREADY_LINKED') {
            anateje_error('USER_ALREADY_LINKED', 'Este email ja esta vinculado a outro associado', 422);
        }
        if ($code === 'ADMIN_USER_NOT_ALLOWED') {
            anateje_error('VALIDATION', 'Nao e permitido vincular usuario administrador como associado', 422);
        }
        if ($code === 'CEP_REQUIRED') {
            anateje_error('VALIDATION', 'CEP e obrigatorio quando houver endereco', 422);
        }
        anateje_error('FAIL', 'Falha ao salvar associado', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro members.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar associado', 500);
    }
}

if ($action === 'admin_save_status') {
    anateje_require_permission($db, $auth, 'admin.associados.edit');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);
    $status = strtoupper(trim((string) ($in['status'] ?? 'ATIVO')));
    $reason = trim((string) ($in['reason'] ?? ''));

    if ($id <= 0 || !in_array($status, ['ATIVO', 'INATIVO'], true)) {
        anateje_error('VALIDATION', 'Dados invalidos para atualizar status', 422);
    }

    $db->beginTransaction();
    try {
        $before = members_fetch_detail($db, $id);
        if (!$before) {
            throw new RuntimeException('MEMBER_NOT_FOUND');
        }

        $oldStatus = (string) ($before['status'] ?? '');
        if ($oldStatus !== $status) {
            $st = $db->prepare('UPDATE members SET status = ? WHERE id = ?');
            $st->execute([$status, $id]);

            $userAtivo = $status === 'ATIVO' ? 1 : 0;
            $su = $db->prepare('UPDATE usuarios SET ativo = ? WHERE id = ?');
            $su->execute([$userAtivo, (int) $before['user_id']]);

            $hist = $db->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
                VALUES (?, ?, ?, ?, ?)');
            $hist->execute([
                $id,
                $oldStatus !== '' ? $oldStatus : null,
                $status,
                $userId,
                $reason !== '' ? $reason : null,
            ]);
        }

        $after = members_fetch_detail($db, $id);
        anateje_audit_log($db, $userId, 'admin.associados', 'status', 'member', $id, $before, $after, ['reason' => $reason]);

        $db->commit();
        anateje_ok(['updated' => true]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e->getMessage() === 'MEMBER_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Associado nao encontrado', 404);
        }
        anateje_error('FAIL', 'Falha ao atualizar status', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro members.admin_save_status: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar status', 500);
    }
}

if ($action === 'admin_bulk_status') {
    anateje_require_permission($db, $auth, 'admin.associados.edit');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $ids = $in['ids'] ?? [];
    $status = strtoupper(trim((string) ($in['status'] ?? '')));
    $reason = trim((string) ($in['reason'] ?? ''));

    if (!is_array($ids)) {
        $ids = [];
    }
    $normalized = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0 && !in_array($id, $normalized, true)) {
            $normalized[] = $id;
        }
    }

    if (empty($normalized)) {
        anateje_error('VALIDATION', 'Selecione ao menos um associado', 422);
    }
    if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
        anateje_error('VALIDATION', 'Status de destino invalido', 422);
    }

    $db->beginTransaction();
    try {
        $updated = 0;
        $unchanged = 0;
        $notFound = 0;
        $applied = [];

        $sb = $db->prepare('SELECT id, user_id, status FROM members WHERE id = ? LIMIT 1');
        $um = $db->prepare('UPDATE members SET status = ? WHERE id = ?');
        $uu = $db->prepare('UPDATE usuarios SET ativo = ? WHERE id = ?');
        $hist = $db->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
            VALUES (?, ?, ?, ?, ?)');

        foreach ($normalized as $id) {
            $sb->execute([$id]);
            $row = $sb->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $notFound++;
                continue;
            }

            $oldStatus = (string) ($row['status'] ?? '');
            if ($oldStatus === $status) {
                $unchanged++;
                continue;
            }

            $um->execute([$status, $id]);
            $uu->execute([$status === 'ATIVO' ? 1 : 0, (int) $row['user_id']]);
            $hist->execute([
                $id,
                $oldStatus !== '' ? $oldStatus : null,
                $status,
                $userId,
                $reason !== '' ? $reason : 'Atualizacao em lote',
            ]);

            $applied[] = $id;
            $updated++;
        }

        if (!empty($applied)) {
            anateje_audit_log(
                $db,
                $userId,
                'admin.associados',
                'bulk_status',
                'member',
                null,
                null,
                null,
                [
                    'ids' => $applied,
                    'target_status' => $status,
                    'reason' => $reason,
                    'updated' => $updated,
                    'unchanged' => $unchanged,
                    'not_found' => $notFound,
                ]
            );
        }

        $db->commit();
        anateje_ok([
            'updated' => $updated,
            'unchanged' => $unchanged,
            'not_found' => $notFound,
            'target_status' => $status,
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro members.admin_bulk_status: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao atualizar status em lote', 500);
    }
}

if ($action === 'admin_delete') {
    anateje_require_permission($db, $auth, 'admin.associados.delete');
    anateje_require_method(['POST']);
    $in = anateje_input();
    $id = (int) ($in['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $db->beginTransaction();
    try {
        $before = members_fetch_detail($db, $id);
        if (!$before) {
            throw new RuntimeException('MEMBER_NOT_FOUND');
        }

        $db->prepare('DELETE FROM addresses WHERE member_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM member_benefits WHERE member_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM event_registrations WHERE member_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM members WHERE id = ?')->execute([$id]);
        $db->prepare('UPDATE usuarios SET ativo = 0 WHERE id = ?')->execute([(int) $before['user_id']]);

        $hist = $db->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
            VALUES (?, ?, ?, ?, ?)');
        $hist->execute([
            $id,
            (string) ($before['status'] ?? 'ATIVO'),
            'INATIVO',
            $userId,
            'Exclusao administrativa',
        ]);

        anateje_audit_log($db, $userId, 'admin.associados', 'delete', 'member', $id, $before, null, []);

        $db->commit();
        anateje_ok(['deleted' => true]);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e->getMessage() === 'MEMBER_NOT_FOUND') {
            anateje_error('NOT_FOUND', 'Associado nao encontrado', 404);
        }
        anateje_error('FAIL', 'Falha ao excluir associado', 500);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro members.admin_delete: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao excluir associado', 500);
    }
}

if ($action === 'admin_import_csv_preview') {
    anateje_require_permission($db, $auth, 'admin.associados.create');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $upsertExisting = !empty($in['upsert_existing']);
    if ($upsertExisting) {
        anateje_require_permission($db, $auth, 'admin.associados.edit');
    }
    $csvText = (string) ($in['csv_text'] ?? '');
    $parsed = members_import_parse_csv_text($csvText, 3000);
    if (empty($parsed['ok'])) {
        anateje_error('VALIDATION', (string) ($parsed['error'] ?? 'CSV invalido'), 422);
    }

    $seenCpf = [];
    $seenEmail = [];
    $seenMatricula = [];
    $rowsOut = [];
    $summary = [
        'total' => 0,
        'ready_create' => 0,
        'ready_update' => 0,
        'skip' => 0,
        'invalid' => 0,
    ];

    foreach ($parsed['rows'] as $row) {
        $summary['total']++;
        $line = (int) ($row['__line'] ?? 0);
        $build = members_import_build_payload($row);
        $payload = $build['payload'];
        $messages = $build['errors'];
        $status = 'READY_CREATE';
        $existingMember = null;

        if ($payload['cpf'] !== '') {
            if (isset($seenCpf[$payload['cpf']])) {
                $messages[] = 'CPF duplicado no arquivo (linha ' . $seenCpf[$payload['cpf']] . ')';
            } else {
                $seenCpf[$payload['cpf']] = $line;
            }
        }

        if ($payload['login_email'] !== '') {
            if (isset($seenEmail[$payload['login_email']])) {
                $messages[] = 'Email de acesso duplicado no arquivo (linha ' . $seenEmail[$payload['login_email']] . ')';
            } else {
                $seenEmail[$payload['login_email']] = $line;
            }
        }

        $mat = (string) ($payload['matricula'] ?? '');
        if ($mat !== '') {
            if (isset($seenMatricula[$mat])) {
                $messages[] = 'Registro associativo duplicado no arquivo (linha ' . $seenMatricula[$mat] . ')';
            } else {
                $seenMatricula[$mat] = $line;
            }
        }

        if (empty($messages)) {
            $existingMember = members_import_lookup_member_by_cpf($db, (string) $payload['cpf']);
            if ($existingMember) {
                if ($upsertExisting) {
                    $status = 'READY_UPDATE';
                    $messages[] = 'CPF existente: atualizara associado ID ' . (int) $existingMember['id'];
                } else {
                    $status = 'SKIP';
                    $messages[] = 'CPF ja cadastrado (ID ' . (int) $existingMember['id'] . ')';
                }
            }
        }

        if (strpos($status, 'READY_') === 0) {
            if ($existingMember) {
                $memberId = (int) ($existingMember['id'] ?? 0);
                $userIdRef = (int) ($existingMember['user_id'] ?? 0);
                foreach (members_import_db_conflicts($db, $payload, $memberId) as $conflict) {
                    $messages[] = $conflict;
                }

                $linkedUser = members_import_lookup_user_by_id($db, $userIdRef);
                if (!$linkedUser) {
                    $messages[] = 'Usuario vinculado ao associado nao encontrado';
                } elseif ((int) ($linkedUser['perfil_id'] ?? 0) === 1) {
                    $messages[] = 'Usuario administrador nao pode ser atualizado via importacao';
                }

                $emailOwner = members_import_lookup_user_by_email($db, (string) $payload['login_email']);
                if ($emailOwner && (int) ($emailOwner['id'] ?? 0) !== $userIdRef) {
                    $messages[] = 'Email de acesso ja utilizado por outro usuario';
                }
            } else {
                foreach (members_import_db_conflicts($db, $payload, 0) as $conflict) {
                    $messages[] = $conflict;
                }

                $emailOwner = members_import_lookup_user_by_email($db, (string) $payload['login_email']);
                if ($emailOwner) {
                    if ((int) ($emailOwner['perfil_id'] ?? 0) === 1) {
                        $messages[] = 'Email de administrador nao permitido';
                    } elseif ((int) ($emailOwner['member_id'] ?? 0) > 0) {
                        $messages[] = 'Email de acesso ja vinculado a outro associado';
                    }
                }
            }

            if (!empty($messages)) {
                $status = 'ERROR';
            }
        }

        if ($status === 'READY_CREATE') {
            $summary['ready_create']++;
        } elseif ($status === 'READY_UPDATE') {
            $summary['ready_update']++;
        } elseif ($status === 'SKIP') {
            $summary['skip']++;
        } else {
            $summary['invalid']++;
        }

        if (count($rowsOut) < 300) {
            $rowsOut[] = [
                'line' => $line,
                'nome' => $payload['nome'],
                'cpf' => $payload['cpf'],
                'login_email' => $payload['login_email'],
                'status' => $status,
                'member_id' => $existingMember ? (int) ($existingMember['id'] ?? 0) : null,
                'messages' => $messages,
            ];
        }
    }

    anateje_ok([
        'summary' => $summary,
        'rows' => $rowsOut,
        'delimiter' => $parsed['delimiter'],
        'mode' => $upsertExisting ? 'upsert' : 'create_only',
    ]);
}

if ($action === 'admin_import_csv_commit') {
    anateje_require_permission($db, $auth, 'admin.associados.create');
    anateje_require_method(['POST']);

    $in = anateje_input();
    $upsertExisting = !empty($in['upsert_existing']);
    if ($upsertExisting) {
        anateje_require_permission($db, $auth, 'admin.associados.edit');
    }
    $csvText = (string) ($in['csv_text'] ?? '');
    $parsed = members_import_parse_csv_text($csvText, 3000);
    if (empty($parsed['ok'])) {
        anateje_error('VALIDATION', (string) ($parsed['error'] ?? 'CSV invalido'), 422);
    }

    $summary = [
        'total' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'invalid' => 0,
        'errors' => [],
        'mode' => $upsertExisting ? 'upsert' : 'create_only',
    ];
    $seenCpf = [];
    $seenEmail = [];
    $seenMatricula = [];

    $db->beginTransaction();
    try {
        foreach ($parsed['rows'] as $row) {
            $summary['total']++;
            $line = (int) ($row['__line'] ?? 0);
            $build = members_import_build_payload($row);
            $payload = $build['payload'];
            $messages = $build['errors'];

            if ($payload['cpf'] !== '') {
                if (isset($seenCpf[$payload['cpf']])) {
                    $messages[] = 'CPF duplicado no arquivo (linha ' . $seenCpf[$payload['cpf']] . ')';
                } else {
                    $seenCpf[$payload['cpf']] = $line;
                }
            }
            if ($payload['login_email'] !== '') {
                if (isset($seenEmail[$payload['login_email']])) {
                    $messages[] = 'Email de acesso duplicado no arquivo (linha ' . $seenEmail[$payload['login_email']] . ')';
                } else {
                    $seenEmail[$payload['login_email']] = $line;
                }
            }
            $mat = (string) ($payload['matricula'] ?? '');
            if ($mat !== '') {
                if (isset($seenMatricula[$mat])) {
                    $messages[] = 'Registro associativo duplicado no arquivo (linha ' . $seenMatricula[$mat] . ')';
                } else {
                    $seenMatricula[$mat] = $line;
                }
            }

            if (!empty($messages)) {
                $summary['invalid']++;
                if (count($summary['errors']) < 300) {
                    $summary['errors'][] = ['line' => $line, 'messages' => $messages];
                }
                continue;
            }

            $existingMember = members_import_lookup_member_by_cpf($db, (string) $payload['cpf']);
            if ($existingMember) {
                if (!$upsertExisting) {
                    $summary['skipped']++;
                    if (count($summary['errors']) < 300) {
                        $summary['errors'][] = ['line' => $line, 'messages' => ['CPF ja cadastrado (ID ' . (int) $existingMember['id'] . ')']];
                    }
                    continue;
                }

                $memberId = (int) ($existingMember['id'] ?? 0);
                $userIdRef = (int) ($existingMember['user_id'] ?? 0);
                foreach (members_import_db_conflicts($db, $payload, $memberId) as $conflict) {
                    $messages[] = $conflict;
                }

                $linkedUser = members_import_lookup_user_by_id($db, $userIdRef);
                if (!$linkedUser) {
                    $messages[] = 'Usuario vinculado ao associado nao encontrado';
                } elseif ((int) ($linkedUser['perfil_id'] ?? 0) === 1) {
                    $messages[] = 'Usuario administrador nao pode ser atualizado via importacao';
                }

                $emailOwner = members_import_lookup_user_by_email($db, (string) $payload['login_email']);
                if ($emailOwner && (int) ($emailOwner['id'] ?? 0) !== $userIdRef) {
                    $messages[] = 'Email de acesso ja utilizado por outro usuario';
                }

                if (!empty($messages)) {
                    $summary['invalid']++;
                    if (count($summary['errors']) < 300) {
                        $summary['errors'][] = ['line' => $line, 'messages' => $messages];
                    }
                    continue;
                }

                $before = members_fetch_detail($db, $memberId);

                $db->prepare('UPDATE usuarios SET nome = ?, ativo = ?, perfil_id = 2 WHERE id = ?')
                    ->execute([$payload['nome'], (int) $payload['user_ativo'], $userIdRef]);
                $db->prepare('UPDATE usuarios SET email = ? WHERE id = ?')->execute([(string) $payload['login_email'], $userIdRef]);

                $stUp = $db->prepare('UPDATE members
                    SET nome=?, lotacao=?, cargo=?, cpf=?, data_filiacao=?, categoria=?, status=?, contribuicao_mensal=?, matricula=?, telefone=?, email_funcional=?
                    WHERE id=?');
                $stUp->execute([
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
                    $memberId,
                ]);

                members_upsert_address($db, $memberId, (array) $payload['address']);

                $oldStatus = (string) ($before['status'] ?? '');
                if ($oldStatus !== (string) $payload['status']) {
                    $hist = $db->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
                        VALUES (?, ?, ?, ?, ?)');
                    $hist->execute([
                        $memberId,
                        $oldStatus !== '' ? $oldStatus : null,
                        $payload['status'],
                        $userId,
                        'Importacao CSV (upsert)',
                    ]);
                }

                $after = members_fetch_detail($db, $memberId);
                anateje_audit_log(
                    $db,
                    $userId,
                    'admin.associados',
                    'import_update',
                    'member',
                    $memberId,
                    $before,
                    $after,
                    ['line' => $line, 'source' => 'csv']
                );

                $summary['updated']++;
                continue;
            }

            foreach (members_import_db_conflicts($db, $payload, 0) as $conflict) {
                $messages[] = $conflict;
            }
            if (!empty($messages)) {
                $summary['invalid']++;
                if (count($summary['errors']) < 300) {
                    $summary['errors'][] = ['line' => $line, 'messages' => $messages];
                }
                continue;
            }

            $existingUser = members_import_lookup_user_by_email($db, (string) $payload['login_email']);
            $userIdRef = 0;
            if ($existingUser) {
                if ((int) ($existingUser['perfil_id'] ?? 0) === 1) {
                    $summary['invalid']++;
                    if (count($summary['errors']) < 300) {
                        $summary['errors'][] = ['line' => $line, 'messages' => ['Email de administrador nao permitido']];
                    }
                    continue;
                }
                if ((int) ($existingUser['member_id'] ?? 0) > 0) {
                    $summary['invalid']++;
                    if (count($summary['errors']) < 300) {
                        $summary['errors'][] = ['line' => $line, 'messages' => ['Email de acesso ja vinculado a outro associado']];
                    }
                    continue;
                }

                $userIdRef = (int) $existingUser['id'];
                $db->prepare('UPDATE usuarios SET nome = ?, ativo = ?, perfil_id = 2 WHERE id = ?')
                    ->execute([$payload['nome'], (int) $payload['user_ativo'], $userIdRef]);
                $db->prepare('UPDATE usuarios SET email = ? WHERE id = ?')->execute([(string) $payload['login_email'], $userIdRef]);
            } else {
                $tempPassword = members_generate_temp_password(10);
                $senhaHash = password_hash($tempPassword, PASSWORD_DEFAULT);
                $iu = $db->prepare('INSERT INTO usuarios (nome, email, senha, perfil_id, ativo) VALUES (?, ?, ?, 2, ?)');
                $iu->execute([$payload['nome'], $payload['login_email'], $senhaHash, (int) $payload['user_ativo']]);
                $userIdRef = (int) $db->lastInsertId();
            }

            $st = $db->prepare('INSERT INTO members
                (nome, lotacao, cargo, cpf, data_filiacao, categoria, status, contribuicao_mensal, matricula, telefone, email_funcional, user_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
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
                $userIdRef,
            ]);
            $memberId = (int) $db->lastInsertId();

            members_upsert_address($db, $memberId, (array) $payload['address']);

            $hist = $db->prepare('INSERT INTO member_status_history (member_id, old_status, new_status, changed_by, reason)
                VALUES (?, NULL, ?, ?, ?)');
            $hist->execute([
                $memberId,
                $payload['status'],
                $userId,
                'Importacao CSV',
            ]);

            anateje_audit_log(
                $db,
                $userId,
                'admin.associados',
                'import_create',
                'member',
                $memberId,
                null,
                members_fetch_detail($db, $memberId),
                ['line' => $line, 'source' => 'csv']
            );

            $summary['created']++;
        }

        $db->commit();
        anateje_ok($summary);
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $code = $e->getMessage();
        if ($code === 'CPF_DUPLICADO') {
            anateje_error('CPF_DUPLICADO', 'Falha na importacao: CPF ja cadastrado', 422, $summary);
        }
        if ($code === 'MATRICULA_DUPLICADA') {
            anateje_error('MATRICULA_DUPLICADA', 'Falha na importacao: registro associativo ja cadastrado', 422, $summary);
        }
        if ($code === 'EMAIL_FUNCIONAL_DUPLICADO') {
            anateje_error('EMAIL_FUNCIONAL_DUPLICADO', 'Falha na importacao: email funcional ja cadastrado', 422, $summary);
        }
        if ($code === 'CEP_REQUIRED') {
            anateje_error('VALIDATION', 'Falha na importacao: CEP obrigatorio quando houver endereco', 422, $summary);
        }
        anateje_error('FAIL', 'Falha na importacao CSV', 500, $summary);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Erro members.admin_import_csv_commit: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha na importacao CSV', 500, $summary);
    }
}

if ($action === 'admin_export_csv') {
    anateje_require_permission($db, $auth, 'admin.associados.export');

    $filters = members_parse_filters();
    $params = [];
    $where = members_where_sql($filters, $params);
    $orderBy = members_sort_sql();

    $sql = "SELECT
            m.id, m.nome, m.cpf, m.categoria, m.status, m.matricula, m.email_funcional, m.telefone,
            m.cargo, m.lotacao, m.data_filiacao, m.contribuicao_mensal, m.created_at,
            u.email AS login_email,
            a.uf, a.cidade
        FROM members m
        LEFT JOIN usuarios u ON u.id = m.user_id
        LEFT JOIN addresses a ON a.member_id = m.id
        $where
        ORDER BY $orderBy";
    $st = $db->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $csvRows = [];
    foreach ($rows as $r) {
        $csvRows[] = [
            (string) ($r['id'] ?? ''),
            (string) ($r['nome'] ?? ''),
            (string) ($r['cpf'] ?? ''),
            (string) ($r['categoria'] ?? ''),
            (string) ($r['status'] ?? ''),
            (string) ($r['matricula'] ?? ''),
            (string) ($r['email_funcional'] ?? ''),
            (string) ($r['login_email'] ?? ''),
            (string) ($r['telefone'] ?? ''),
            (string) ($r['cargo'] ?? ''),
            (string) ($r['lotacao'] ?? ''),
            (string) ($r['data_filiacao'] ?? ''),
            (string) ($r['contribuicao_mensal'] ?? ''),
            (string) ($r['uf'] ?? ''),
            (string) ($r['cidade'] ?? ''),
            (string) ($r['created_at'] ?? ''),
        ];
    }

    members_csv(
        'associados-' . date('Ymd-His') . '.csv',
        ['id', 'nome', 'cpf', 'categoria', 'status', 'matricula', 'email_funcional', 'login_email', 'telefone', 'cargo', 'lotacao', 'data_filiacao', 'contribuicao_mensal', 'uf', 'cidade', 'created_at'],
        $csvRows
    );
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
