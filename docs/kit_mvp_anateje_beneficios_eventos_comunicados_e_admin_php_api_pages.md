# KIT MVP — ANATEJE
Benefícios + Eventos + Comunicados + Admin (CRUD básico)

Este kit presume que você já tem:
- /app/config/env.php, /app/config/database.php
- /app/middleware/cors.php, /app/middleware/auth_middleware.php
- /public/assets/js/api.js
- auth.php (login), members.php (perfil), utils.php (ViaCEP)

## 1) API — Benefícios

Arquivo: /app/api/v1/benefits.php

Funcionalidades:
- list: lista benefícios (catálogo) e marca os ativos do associado (member_benefits)
- set_member_benefits: atualiza benefícios ativos do associado
- admin_crud: CRUD básico para benefícios (admin)

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';

cors();
$auth = require_auth();
$action = $_GET['action'] ?? '';

function json_input(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function get_member_id(PDO $pdo, int $userId): ?int {
  $st = $pdo->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
  $st->execute([$userId]);
  $r = $st->fetch();
  return $r ? (int)$r['id'] : null;
}

if ($action === 'list') {
  $pdo = db();
  $memberId = get_member_id($pdo, (int)$auth['sub']);

  $benefits = $pdo->query("SELECT id, nome, descricao, link, status, sort_order FROM benefits WHERE status='active' ORDER BY sort_order ASC, id ASC")->fetchAll();

  $activeMap = [];
  if ($memberId) {
    $st = $pdo->prepare('SELECT benefit_id, ativo FROM member_benefits WHERE member_id = ?');
    $st->execute([$memberId]);
    foreach ($st->fetchAll() as $row) {
      $activeMap[(int)$row['benefit_id']] = (int)$row['ativo'] === 1;
    }
  }

  foreach ($benefits as &$b) {
    $b['active_for_me'] = $memberId ? (bool)($activeMap[(int)$b['id']] ?? false) : false;
  }

  echo json_encode(['ok' => true, 'data' => ['benefits' => $benefits]]);
  exit;
}

if ($action === 'set_member_benefits') {
  $pdo = db();
  $memberId = get_member_id($pdo, (int)$auth['sub']);
  if (!$memberId) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'NO_MEMBER', 'message' => 'Complete seu perfil antes']]);
    exit;
  }

  $in = json_input();
  $benefitIds = $in['benefit_ids'] ?? [];
  if (!is_array($benefitIds)) $benefitIds = [];

  $pdo->beginTransaction();
  try {
    // Remove tudo e recria (simples e seguro para MVP)
    $st = $pdo->prepare('DELETE FROM member_benefits WHERE member_id = ?');
    $st->execute([$memberId]);

    $stIns = $pdo->prepare('INSERT INTO member_benefits (member_id, benefit_id, ativo) VALUES (?,?,1)');
    foreach ($benefitIds as $id) {
      $id = (int)$id;
      if ($id > 0) $stIns->execute([$memberId, $id]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['updated' => true]]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => 'Falha ao atualizar benefícios']]);
    exit;
  }
}

// ADMIN: CRUD
if ($action === 'admin_list') {
  require_admin($auth);
  $pdo = db();
  $rows = $pdo->query('SELECT * FROM benefits ORDER BY sort_order ASC, id ASC')->fetchAll();
  echo json_encode(['ok' => true, 'data' => ['benefits' => $rows]]);
  exit;
}

if ($action === 'admin_save') {
  require_admin($auth);
  $in = json_input();
  $pdo = db();

  $id = (int)($in['id'] ?? 0);
  $nome = trim((string)($in['nome'] ?? ''));
  if (!$nome) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Nome é obrigatório']]);
    exit;
  }

  $descricao = (string)($in['descricao'] ?? '');
  $link = (string)($in['link'] ?? '');
  $status = ($in['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
  $sort = (int)($in['sort_order'] ?? 0);

  $pdo->beginTransaction();
  try {
    if ($id > 0) {
      $st = $pdo->prepare('UPDATE benefits SET nome=?, descricao=?, link=?, status=?, sort_order=? WHERE id=?');
      $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $sort, $id]);
    } else {
      $st = $pdo->prepare('INSERT INTO benefits (nome, descricao, link, status, sort_order) VALUES (?,?,?,?,?)');
      $st->execute([$nome, $descricao ?: null, $link ?: null, $status, $sort]);
      $id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['id' => $id]]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => 'Falha ao salvar benefício']]);
    exit;
  }
}

if ($action === 'admin_delete') {
  require_admin($auth);
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'ID inválido']]);
    exit;
  }

  $pdo = db();
  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare('DELETE FROM benefits WHERE id=?');
    $st->execute([$id]);
    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['deleted' => true]]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => 'Falha ao excluir benefício']]);
    exit;
  }
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Ação inválida']]);
```

## 2) API — Eventos

Arquivo: /app/api/v1/events.php

Funcionalidades:
- list: lista eventos publicados
- detail: detalhe do evento
- register: inscrição
- cancel: cancelamento
- admin_list/admin_save/admin_delete: CRUD admin

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';

cors();
$auth = require_auth();
$action = $_GET['action'] ?? '';

function json_input(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function get_member_id(PDO $pdo, int $userId): ?int {
  $st = $pdo->prepare('SELECT id FROM members WHERE user_id = ? LIMIT 1');
  $st->execute([$userId]);
  $r = $st->fetch();
  return $r ? (int)$r['id'] : null;
}

if ($action === 'list') {
  $pdo = db();
  $rows = $pdo->query("SELECT id, titulo, local, inicio_em, fim_em, vagas, status, imagem_url, link FROM events WHERE status='published' ORDER BY inicio_em ASC")->fetchAll();
  echo json_encode(['ok' => true, 'data' => ['events' => $rows]]);
  exit;
}

if ($action === 'detail') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'ID inválido']]);
    exit;
  }

  $pdo = db();
  $st = $pdo->prepare('SELECT * FROM events WHERE id=? LIMIT 1');
  $st->execute([$id]);
  $ev = $st->fetch();
  if (!$ev || $ev['status'] !== 'published') {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Evento não encontrado']]);
    exit;
  }

  $memberId = get_member_id($pdo, (int)$auth['sub']);
  $reg = null;
  if ($memberId) {
    $st = $pdo->prepare('SELECT status FROM event_registrations WHERE event_id=? AND member_id=? LIMIT 1');
    $st->execute([$id, $memberId]);
    $reg = $st->fetch() ?: null;
  }

  echo json_encode(['ok' => true, 'data' => ['event' => $ev, 'registration' => $reg]]);
  exit;
}

if ($action === 'register') {
  $in = json_input();
  $eventId = (int)($in['event_id'] ?? 0);

  $pdo = db();
  $memberId = get_member_id($pdo, (int)$auth['sub']);
  if (!$memberId) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'NO_MEMBER', 'message' => 'Complete seu perfil antes']]);
    exit;
  }

  $st = $pdo->prepare('SELECT id, vagas, status FROM events WHERE id=? LIMIT 1');
  $st->execute([$eventId]);
  $ev = $st->fetch();
  if (!$ev || $ev['status'] !== 'published') {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Evento não encontrado']]);
    exit;
  }

  $pdo->beginTransaction();
  try {
    if (!is_null($ev['vagas'])) {
      $st = $pdo->prepare("SELECT COUNT(*) AS c FROM event_registrations WHERE event_id=? AND status='registered'");
      $st->execute([$eventId]);
      $count = (int)($st->fetch()['c'] ?? 0);
      if ($count >= (int)$ev['vagas']) {
        throw new Exception('SEM_VAGAS');
      }
    }

    $st = $pdo->prepare('INSERT INTO event_registrations (event_id, member_id, status) VALUES (?,?,\'registered\')
      ON DUPLICATE KEY UPDATE status=VALUES(status)');
    $st->execute([$eventId, $memberId]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['registered' => true]]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    $code = $e->getMessage();
    $msg = $code === 'SEM_VAGAS' ? 'Evento sem vagas' : 'Falha ao registrar';
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => $code, 'message' => $msg]]);
    exit;
  }
}

if ($action === 'cancel') {
  $in = json_input();
  $eventId = (int)($in['event_id'] ?? 0);
  $pdo = db();

  $memberId = get_member_id($pdo, (int)$auth['sub']);
  if (!$memberId) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'NO_MEMBER', 'message' => 'Complete seu perfil antes']]);
    exit;
  }

  $st = $pdo->prepare('UPDATE event_registrations SET status=\'canceled\' WHERE event_id=? AND member_id=?');
  $st->execute([$eventId, $memberId]);

  echo json_encode(['ok' => true, 'data' => ['canceled' => true]]);
  exit;
}

// ADMIN CRUD
if ($action === 'admin_list') {
  require_admin($auth);
  $pdo = db();
  $rows = $pdo->query('SELECT * FROM events ORDER BY inicio_em DESC')->fetchAll();
  echo json_encode(['ok' => true, 'data' => ['events' => $rows]]);
  exit;
}

if ($action === 'admin_save') {
  require_admin($auth);
  $in = json_input();
  $pdo = db();

  $id = (int)($in['id'] ?? 0);
  $titulo = trim((string)($in['titulo'] ?? ''));
  $inicio = (string)($in['inicio_em'] ?? '');
  if (!$titulo || !$inicio) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Título e início são obrigatórios']]);
    exit;
  }

  $descricao = (string)($in['descricao'] ?? '');
  $local = (string)($in['local'] ?? '');
  $fim = (string)($in['fim_em'] ?? '');
  $vagas = isset($in['vagas']) && $in['vagas'] !== '' ? (int)$in['vagas'] : null;
  $status = in_array(($in['status'] ?? 'draft'), ['draft','published','archived'], true) ? $in['status'] : 'draft';
  $imagem = (string)($in['imagem_url'] ?? '');
  $link = (string)($in['link'] ?? '');

  $pdo->beginTransaction();
  try {
    if ($id > 0) {
      $st = $pdo->prepare('UPDATE events SET titulo=?, descricao=?, local=?, inicio_em=?, fim_em=?, vagas=?, status=?, imagem_url=?, link=? WHERE id=?');
      $st->execute([$titulo, $descricao ?: null, $local ?: null, $inicio, $fim ?: null, $vagas, $status, $imagem ?: null, $link ?: null, $id]);
    } else {
      $st = $pdo->prepare('INSERT INTO events (titulo, descricao, local, inicio_em, fim_em, vagas, status, imagem_url, link) VALUES (?,?,?,?,?,?,?,?,?)');
      $st->execute([$titulo, $descricao ?: null, $local ?: null, $inicio, $fim ?: null, $vagas, $status, $imagem ?: null, $link ?: null]);
      $id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['id' => $id]]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => 'Falha ao salvar evento']]);
    exit;
  }
}

if ($action === 'admin_delete') {
  require_admin($auth);
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'ID inválido']]);
    exit;
  }

  $pdo = db();
  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare('DELETE FROM events WHERE id=?');
    $st->execute([$id]);
    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['deleted' => true]]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => 'Falha ao excluir evento']]);
    exit;
  }
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Ação inválida']]);
```

## 3) API — Comunicados (posts tipo COMUNICADO)

Arquivo: /app/api/v1/posts.php

Funcionalidades:
- list: lista comunicados publicados
- detail: detalhe por id
- admin_list/admin_save/admin_delete: CRUD admin

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';

cors();
$auth = require_auth();
$action = $_GET['action'] ?? '';

function json_input(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

if ($action === 'list') {
  $type = ($_GET['type'] ?? 'COMUNICADO') === 'BLOG' ? 'BLOG' : 'COMUNICADO';
  $pdo = db();

  $st = $pdo->prepare("SELECT id, tipo, titulo, slug, status, publicado_em, created_at FROM posts WHERE tipo=? AND status='published' ORDER BY publicado_em DESC, id DESC LIMIT 50");
  $st->execute([$type]);
  $rows = $st->fetchAll();

  echo json_encode(['ok' => true, 'data' => ['posts' => $rows]]);
  exit;
}

if ($action === 'detail') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'ID inválido']]);
    exit;
  }

  $pdo = db();
  $st = $pdo->prepare("SELECT * FROM posts WHERE id=? AND status='published' LIMIT 1");
  $st->execute([$id]);
  $p = $st->fetch();
  if (!$p) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Post não encontrado']]);
    exit;
  }

  echo json_encode(['ok' => true, 'data' => ['post' => $p]]);
  exit;
}

// ADMIN CRUD
if ($action === 'admin_list') {
  require_admin($auth);
  $pdo = db();
  $rows = $pdo->query('SELECT id, tipo, titulo, slug, status, publicado_em, created_at FROM posts ORDER BY created_at DESC')->fetchAll();
  echo json_encode(['ok' => true, 'data' => ['posts' => $rows]]);
  exit;
}

if ($action === 'admin_save') {
  require_admin($auth);
  $in = json_input();
  $pdo = db();

  $id = (int)($in['id'] ?? 0);
  $tipo = ($in['tipo'] ?? 'COMUNICADO') === 'BLOG' ? 'BLOG' : 'COMUNICADO';
  $titulo = trim((string)($in['titulo'] ?? ''));
  $conteudo = (string)($in['conteudo'] ?? '');
  $status = in_array(($in['status'] ?? 'draft'), ['draft','published','archived'], true) ? $in['status'] : 'draft';

  if (!$titulo) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Título é obrigatório']]);
    exit;
  }

  // slug simples
  $slug = strtolower(trim((string)($in['slug'] ?? '')));
  if (!$slug) {
    $slug = preg_replace('/[^a-z0-9\-]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $titulo));
    $slug = trim(preg_replace('/-+/', '-', $slug), '-');
  }

  $publicado = ($status === 'published') ? (string)($in['publicado_em'] ?? '') : '';
  if ($status === 'published' && !$publicado) {
    $publicado = date('Y-m-d H:i:s');
  }

  $pdo->beginTransaction();
  try {
    if ($id > 0) {
      $st = $pdo->prepare('UPDATE posts SET tipo=?, titulo=?, slug=?, conteudo=?, status=?, publicado_em=? WHERE id=?');
      $st->execute([$tipo, $titulo, $slug ?: null, $conteudo ?: null, $status, $publicado ?: null, $id]);
    } else {
      $st = $pdo->prepare('INSERT INTO posts (tipo, titulo, slug, conteudo, status, publicado_em) VALUES (?,?,?,?,?,?)');
      $st->execute([$tipo, $titulo, $slug ?: null, $conteudo ?: null, $status, $publicado ?: null]);
      $id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['id' => $id]]);
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => ['code' => 'FAIL', 'message' => 'Falha ao salvar post']]);
    exit;
  }
}

if ($action === 'admin_delete') {
  require_admin($auth);
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'ID inválido']]);
    exit;
  }

  $pdo = db();
  $st = $pdo->prepare('DELETE FROM posts WHERE id=?');
  $st->execute([$id]);

  echo json_encode(['ok' => true, 'data' => ['deleted' => true]]);
  exit;
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Ação inválida']]);
```

## 4) Páginas do Associado (.php) — Benefícios, Eventos e Comunicados

### 4.1 /public/meus-beneficios.php
- Lista benefícios
- Permite “marcar ativos” (MVP: o próprio associado marca os benefícios que usa)

```php
<?php
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meus Benefícios</title>
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-neutral text-neutral-content">

<div class="max-w-5xl mx-auto p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Meus benefícios</h1>
    <a class="btn btn-ghost" href="/dashboard.php">Voltar</a>
  </div>

  <div id="list" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6"></div>

  <div class="mt-6 flex gap-2">
    <button id="save" class="btn btn-primary">Salvar</button>
    <span id="msg" class="text-sm"></span>
  </div>
</div>

<script type="module">
  import { api, getToken } from '/assets/js/api.js';
  if (!getToken()) window.location.href = '/login.php';

  const list = document.getElementById('list');
  const save = document.getElementById('save');
  const msg = document.getElementById('msg');

  let benefits = [];

  function render() {
    list.innerHTML = benefits.map(b => `
      <div class="card bg-base-100 text-base-content shadow">
        <div class="card-body">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="font-bold text-lg">${b.nome}</h3>
              <p class="text-sm opacity-80">${b.descricao || ''}</p>
              ${b.link ? `<a class="link link-primary" href="${b.link}" target="_blank">Acessar</a>` : ''}
            </div>
            <label class="label cursor-pointer">
              <span class="label-text">Ativo</span>
              <input type="checkbox" class="toggle toggle-primary" data-id="${b.id}" ${b.active_for_me ? 'checked' : ''} />
            </label>
          </div>
        </div>
      </div>
    `).join('');
  }

  async function load() {
    const r = await api('/app/api/v1/benefits.php?action=list');
    benefits = r.data.benefits || [];
    render();
  }

  save.addEventListener('click', async () => {
    msg.textContent = '';
    try {
      const ids = Array.from(document.querySelectorAll('input[type=checkbox][data-id]'))
        .filter(el => el.checked)
        .map(el => parseInt(el.dataset.id, 10));

      await api('/app/api/v1/benefits.php?action=set_member_benefits', {
        method: 'POST',
        body: { benefit_ids: ids }
      });

      msg.textContent = 'Salvo';
      msg.className = 'text-sm text-success';
    } catch (e) {
      msg.textContent = e.message;
      msg.className = 'text-sm text-error';
    }
  });

  load();
</script>
</body>
</html>
```

### 4.2 /public/meus-eventos.php
- Lista eventos
- Botão inscrever/cancelar

```php
<?php
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Eventos</title>
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-neutral text-neutral-content">

<div class="max-w-5xl mx-auto p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Eventos</h1>
    <a class="btn btn-ghost" href="/dashboard.php">Voltar</a>
  </div>

  <div id="list" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6"></div>
  <p id="msg" class="mt-4 text-sm"></p>
</div>

<script type="module">
  import { api, getToken } from '/assets/js/api.js';
  if (!getToken()) window.location.href = '/login.php';

  const list = document.getElementById('list');
  const msg = document.getElementById('msg');

  async function load() {
    const r = await api('/app/api/v1/events.php?action=list');
    const events = r.data.events || [];

    list.innerHTML = events.map(ev => `
      <div class="card bg-base-100 text-base-content shadow">
        <div class="card-body">
          <h3 class="text-lg font-bold">${ev.titulo}</h3>
          <p class="text-sm opacity-80">${ev.local || ''}</p>
          <p class="text-sm opacity-80">Início: ${ev.inicio_em}</p>
          <div class="mt-3 flex gap-2">
            <button class="btn btn-primary" data-act="reg" data-id="${ev.id}">Inscrever</button>
            <button class="btn btn-outline" data-act="cancel" data-id="${ev.id}">Cancelar</button>
            <button class="btn btn-ghost" data-act="detail" data-id="${ev.id}">Detalhes</button>
          </div>
        </div>
      </div>
    `).join('');

    list.querySelectorAll('button[data-act]').forEach(btn => {
      btn.addEventListener('click', async () => {
        msg.textContent = '';
        try {
          const id = parseInt(btn.dataset.id, 10);
          const act = btn.dataset.act;

          if (act === 'detail') {
            const d = await api('/app/api/v1/events.php?action=detail&id=' + id);
            alert((d.data.event?.titulo || '') + "\n\n" + (d.data.event?.descricao || ''));
            return;
          }

          if (act === 'reg') {
            await api('/app/api/v1/events.php?action=register', { method: 'POST', body: { event_id: id } });
            msg.textContent = 'Inscrito com sucesso';
            msg.className = 'mt-4 text-sm text-success';
          }

          if (act === 'cancel') {
            await api('/app/api/v1/events.php?action=cancel', { method: 'POST', body: { event_id: id } });
            msg.textContent = 'Cancelado';
            msg.className = 'mt-4 text-sm text-success';
          }

        } catch (e) {
          msg.textContent = e.message;
          msg.className = 'mt-4 text-sm text-error';
        }
      });
    });
  }

  load();
</script>
</body>
</html>
```

### 4.3 /public/comunicados.php

```php
<?php
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Comunicados</title>
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-neutral text-neutral-content">

<div class="max-w-5xl mx-auto p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Comunicados</h1>
    <a class="btn btn-ghost" href="/dashboard.php">Voltar</a>
  </div>

  <div id="list" class="grid grid-cols-1 gap-4 mt-6"></div>
  <p id="msg" class="mt-4 text-sm"></p>
</div>

<script type="module">
  import { api, getToken } from '/assets/js/api.js';
  if (!getToken()) window.location.href = '/login.php';

  const list = document.getElementById('list');
  const msg = document.getElementById('msg');

  async function load() {
    try {
      const r = await api('/app/api/v1/posts.php?action=list&type=COMUNICADO');
      const posts = r.data.posts || [];

      list.innerHTML = posts.map(p => `
        <div class="card bg-base-100 text-base-content shadow">
          <div class="card-body">
            <div class="text-xs opacity-70">${p.publicado_em || p.created_at}</div>
            <h3 class="text-lg font-bold">${p.titulo}</h3>
            <div class="mt-2">
              <button class="btn btn-sm btn-primary" data-id="${p.id}">Ler</button>
            </div>
          </div>
        </div>
      `).join('');

      list.querySelectorAll('button[data-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
          try {
            const id = parseInt(btn.dataset.id, 10);
            const d = await api('/app/api/v1/posts.php?action=detail&id=' + id);
            alert((d.data.post?.titulo || '') + "\n\n" + (d.data.post?.conteudo || ''));
          } catch (e) {
            msg.textContent = e.message;
            msg.className = 'mt-4 text-sm text-error';
          }
        });
      });

    } catch (e) {
      msg.textContent = e.message;
      msg.className = 'mt-4 text-sm text-error';
    }
  }

  load();
</script>
</body>
</html>
```

## 5) Admin — páginas (.php) mínimas

Recomendação:
- Criar um /admin/index.php que verifica admin com /app/api/v1/auth.php?action=me e valida role
- Todas páginas admin consumindo APIs admin_* com require_admin

### 5.1 /admin/beneficios.php

```php
<?php
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Benefícios</title>
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-neutral text-neutral-content">

<div class="max-w-6xl mx-auto p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Admin • Benefícios</h1>
    <a class="btn btn-ghost" href="/admin/dashboard.php">Voltar</a>
  </div>

  <div class="card bg-base-100 text-base-content shadow mt-6">
    <div class="card-body">
      <div class="flex gap-2">
        <button id="new" class="btn btn-primary">Novo</button>
      </div>

      <div class="overflow-x-auto mt-4">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Status</th>
              <th>Ordem</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="rows"></tbody>
        </table>
      </div>

      <dialog id="modal" class="modal">
        <div class="modal-box">
          <h3 class="font-bold text-lg">Benefício</h3>

          <input type="hidden" id="id" />

          <label class="form-control mt-3">
            <span class="label-text">Nome</span>
            <input id="nome" class="input input-bordered" />
          </label>

          <label class="form-control mt-3">
            <span class="label-text">Descrição</span>
            <textarea id="descricao" class="textarea textarea-bordered"></textarea>
          </label>

          <label class="form-control mt-3">
            <span class="label-text">Link</span>
            <input id="link" class="input input-bordered" />
          </label>

          <div class="grid grid-cols-2 gap-3 mt-3">
            <label class="form-control">
              <span class="label-text">Status</span>
              <select id="status" class="select select-bordered">
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
              </select>
            </label>
            <label class="form-control">
              <span class="label-text">Ordem</span>
              <input id="sort_order" type="number" class="input input-bordered" />
            </label>
          </div>

          <div class="modal-action">
            <button id="save" class="btn btn-primary">Salvar</button>
            <form method="dialog"><button class="btn">Fechar</button></form>
          </div>
          <p id="msg" class="text-sm mt-2"></p>
        </div>
      </dialog>

    </div>
  </div>
</div>

<script type="module">
  import { api, getToken } from '/assets/js/api.js';
  if (!getToken()) window.location.href = '/login.php';

  const rows = document.getElementById('rows');
  const modal = document.getElementById('modal');
  const msg = document.getElementById('msg');

  const el = id => document.getElementById(id);

  function openModal(data = null) {
    el('id').value = data?.id || '';
    el('nome').value = data?.nome || '';
    el('descricao').value = data?.descricao || '';
    el('link').value = data?.link || '';
    el('status').value = data?.status || 'active';
    el('sort_order').value = data?.sort_order ?? 0;
    msg.textContent = '';
    modal.showModal();
  }

  async function load() {
    const r = await api('/app/api/v1/benefits.php?action=admin_list');
    const list = r.data.benefits || [];
    rows.innerHTML = list.map(b => `
      <tr>
        <td>${b.id}</td>
        <td>${b.nome}</td>
        <td>${b.status}</td>
        <td>${b.sort_order}</td>
        <td class="flex gap-2">
          <button class="btn btn-sm" data-edit="${b.id}">Editar</button>
          <button class="btn btn-sm btn-outline" data-del="${b.id}">Excluir</button>
        </td>
      </tr>
    `).join('');

    rows.querySelectorAll('button[data-edit]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.edit, 10);
        const b = list.find(x => parseInt(x.id, 10) === id);
        openModal(b);
      });
    });

    rows.querySelectorAll('button[data-del]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Excluir este benefício?')) return;
        const id = parseInt(btn.dataset.del, 10);
        await api('/app/api/v1/benefits.php?action=admin_delete&id=' + id);
        load();
      });
    });
  }

  document.getElementById('new').addEventListener('click', () => openModal());

  document.getElementById('save').addEventListener('click', async () => {
    msg.textContent = '';
    try {
      const body = {
        id: el('id').value ? parseInt(el('id').value, 10) : 0,
        nome: el('nome').value,
        descricao: el('descricao').value,
        link: el('link').value,
        status: el('status').value,
        sort_order: parseInt(el('sort_order').value || '0', 10)
      };

      await api('/app/api/v1/benefits.php?action=admin_save', { method: 'POST', body });
      msg.textContent = 'Salvo';
      msg.className = 'text-sm text-success mt-2';
      modal.close();
      load();
    } catch (e) {
      msg.textContent = e.message;
      msg.className = 'text-sm text-error mt-2';
    }
  });

  load();
</script>
</body>
</html>
```

Repita a mesma lógica para:
- /admin/eventos.php (usando /app/api/v1/events.php?action=admin_*)
- /admin/comunicados.php (usando /app/api/v1/posts.php?action=admin_*)

## 6) Nota importante (MVP)
No MVP, para simplificar, o associado marca benefícios “ativos” por conta própria.
Se a regra correta for: somente admin ativa benefícios por associado, troque:
- meus-beneficios.php para “somente leitura”
- crie um admin-associado-beneficios.php com seleção do associado + checkboxes

## 7) Rotas para adicionar no menu do associado
- /perfil.php
- /meus-beneficios.php
- /meus-eventos.php
- /comunicados.php

## 8) Rotas para adicionar no menu admin
- /admin/dashboard.php
- /admin/beneficios.php
- /admin/eventos.php
- /admin/comunicados.php

