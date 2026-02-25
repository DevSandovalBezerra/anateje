<?php

require_once __DIR__ . '/_bootstrap.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $type = strtoupper(trim((string) ($_GET['type'] ?? 'COMUNICADO')));
    if (!in_array($type, ['COMUNICADO', 'BLOG'], true)) {
        $type = 'COMUNICADO';
    }

    $st = $db->prepare("SELECT id, tipo, titulo, slug, status, publicado_em, created_at
        FROM posts
        WHERE tipo = ? AND status = 'published'
        ORDER BY publicado_em DESC, id DESC
        LIMIT 100");
    $st->execute([$type]);

    anateje_ok(['posts' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $st = $db->prepare("SELECT * FROM posts WHERE id = ? AND status = 'published' LIMIT 1");
    $st->execute([$id]);
    $post = $st->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        anateje_error('NOT_FOUND', 'Post nao encontrado', 404);
    }

    anateje_ok(['post' => $post]);
}

if ($action === 'admin_list') {
    anateje_require_admin($auth);

    $rows = $db->query('SELECT id, tipo, titulo, slug, conteudo, status, publicado_em, created_at FROM posts ORDER BY created_at DESC, id DESC')
        ->fetchAll(PDO::FETCH_ASSOC);

    anateje_ok(['posts' => $rows]);
}

if ($action === 'admin_get') {
    anateje_require_admin($auth);

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $st = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $post = $st->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        anateje_error('NOT_FOUND', 'Post nao encontrado', 404);
    }

    anateje_ok(['post' => $post]);
}

if ($action === 'admin_save') {
    anateje_require_admin($auth);
    anateje_require_method(['POST']);

    $in = anateje_input();
    $id = (int) ($in['id'] ?? 0);

    $tipo = strtoupper(trim((string) ($in['tipo'] ?? 'COMUNICADO')));
    if (!in_array($tipo, ['COMUNICADO', 'BLOG'], true)) {
        $tipo = 'COMUNICADO';
    }

    $titulo = trim((string) ($in['titulo'] ?? ''));
    if ($titulo === '') {
        anateje_error('VALIDATION', 'Titulo e obrigatorio', 422);
    }

    $status = strtolower(trim((string) ($in['status'] ?? 'draft')));
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $status = 'draft';
    }

    $slug = trim((string) ($in['slug'] ?? ''));
    if ($slug === '') {
        $slug = anateje_slug($titulo);
    } else {
        $slug = anateje_slug($slug);
    }

    $conteudo = trim((string) ($in['conteudo'] ?? ''));
    $publicadoEm = anateje_parse_datetime($in['publicado_em'] ?? '');
    if ($status === 'published' && $publicadoEm === null) {
        $publicadoEm = date('Y-m-d H:i:s');
    }
    if ($status !== 'published') {
        $publicadoEm = null;
    }

    $db->beginTransaction();
    try {
        if ($id > 0) {
            $st = $db->prepare('UPDATE posts SET tipo=?, titulo=?, slug=?, conteudo=?, status=?, publicado_em=? WHERE id=?');
            $st->execute([$tipo, $titulo, $slug ?: null, $conteudo ?: null, $status, $publicadoEm, $id]);
        } else {
            $st = $db->prepare('INSERT INTO posts (tipo, titulo, slug, conteudo, status, publicado_em) VALUES (?,?,?,?,?,?)');
            $st->execute([$tipo, $titulo, $slug ?: null, $conteudo ?: null, $status, $publicadoEm]);
            $id = (int) $db->lastInsertId();
        }

        $db->commit();
        anateje_ok(['id' => $id]);
    } catch (PDOException $e) {
        $db->rollBack();
        if ((int) $e->errorInfo[1] === 1062) {
            anateje_error('SLUG_DUPLICADO', 'Slug ja existente, informe outro slug', 422);
        }

        logError('Erro posts.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar post', 500);
    } catch (Throwable $e) {
        $db->rollBack();
        logError('Erro posts.admin_save: ' . $e->getMessage());
        anateje_error('FAIL', 'Falha ao salvar post', 500);
    }
}

if ($action === 'admin_delete') {
    anateje_require_admin($auth);

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        anateje_error('VALIDATION', 'ID invalido', 422);
    }

    $st = $db->prepare('DELETE FROM posts WHERE id = ?');
    $st->execute([$id]);

    anateje_ok(['deleted' => true]);
}

anateje_error('NOT_FOUND', 'Acao invalida', 404);
