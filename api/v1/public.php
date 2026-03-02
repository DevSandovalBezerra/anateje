<?php

require_once __DIR__ . '/_bootstrap.php';

$db = getDB();
anateje_ensure_schema($db);

function public_ensure_tables(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS public_leads (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL,
        telefone VARCHAR(30) NOT NULL,
        uf CHAR(2) NOT NULL,
        categoria ENUM('PARCIAL','INTEGRAL') NOT NULL DEFAULT 'PARCIAL',
        origem VARCHAR(120) NULL,
        consentimento TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_public_leads_email (email),
        KEY idx_public_leads_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS public_contacts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL,
        telefone VARCHAR(30) NULL,
        assunto VARCHAR(160) NOT NULL,
        mensagem TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_public_contacts_email (email),
        KEY idx_public_contacts_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

public_ensure_tables($db);

$action = $_GET['action'] ?? '';

if ($action === 'lead_capture') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $nome = trim((string) ($in['nome'] ?? ''));
    $email = strtolower(trim((string) ($in['email'] ?? '')));
    $telefone = trim((string) ($in['telefone'] ?? ''));
    $uf = strtoupper(trim((string) ($in['uf'] ?? '')));
    $categoria = strtoupper(trim((string) ($in['categoria'] ?? 'PARCIAL')));
    $origem = trim((string) ($in['origem'] ?? ''));
    $consentimento = !empty($in['consentimento']) ? 1 : 0;

    if ($nome === '' || $email === '' || $telefone === '') {
        anateje_error('VALIDATION', 'Nome, email e telefone sao obrigatorios', 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        anateje_error('VALIDATION', 'Email invalido', 422);
    }
    if (!preg_match('/^[A-Z]{2}$/', $uf)) {
        anateje_error('VALIDATION', 'UF invalida', 422);
    }
    if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
        $categoria = 'PARCIAL';
    }
    if ($consentimento !== 1) {
        anateje_error('VALIDATION', 'Consentimento obrigatorio', 422);
    }

    $st = $db->prepare('INSERT INTO public_leads (nome, email, telefone, uf, categoria, origem, consentimento) VALUES (?,?,?,?,?,?,?)');
    $st->execute([
        $nome,
        $email,
        $telefone,
        $uf,
        $categoria,
        $origem !== '' ? $origem : null,
        $consentimento
    ]);

    anateje_ok(['saved' => true, 'id' => (int) $db->lastInsertId()]);
}

if ($action === 'contact') {
    anateje_require_method(['POST']);

    $in = anateje_input();
    $nome = trim((string) ($in['nome'] ?? ''));
    $email = strtolower(trim((string) ($in['email'] ?? '')));
    $telefone = trim((string) ($in['telefone'] ?? ''));
    $assunto = trim((string) ($in['assunto'] ?? ''));
    $mensagem = trim((string) ($in['mensagem'] ?? ''));

    if ($nome === '' || $email === '' || $assunto === '' || $mensagem === '') {
        anateje_error('VALIDATION', 'Nome, email, assunto e mensagem sao obrigatorios', 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        anateje_error('VALIDATION', 'Email invalido', 422);
    }

    $st = $db->prepare('INSERT INTO public_contacts (nome, email, telefone, assunto, mensagem) VALUES (?,?,?,?,?)');
    $st->execute([
        $nome,
        $email,
        $telefone !== '' ? $telefone : null,
        $assunto,
        $mensagem
    ]);

    anateje_ok(['saved' => true, 'id' => (int) $db->lastInsertId()]);
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);

