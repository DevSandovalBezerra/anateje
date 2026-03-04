<?php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/base_path.php';

$auth = anateje_require_auth();
$db = getDB();
anateje_ensure_schema($db);

function member_folders_now_user_id(array $auth): int
{
    return (int) ($auth['sub'] ?? 0);
}

function member_folders_request_input(): array
{
    $json = anateje_input();
    if (!empty($json)) {
        return $json;
    }

    if (!empty($_POST) && is_array($_POST)) {
        return $_POST;
    }

    return [];
}

function member_folders_download_url(int $fileId): string
{
    $prefix = lidergest_base_prefix();
    return $prefix . 'api/v1/member_folders.php?action=admin_download&id=' . (int) $fileId;
}

function member_folders_find_folder(PDO $db, int $folderId): ?array
{
    $st = $db->prepare("SELECT id, member_id, parent_id, tipo, nome, status, created_at, updated_at
        FROM member_folders
        WHERE id = ?
        LIMIT 1");
    $st->execute([$folderId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function member_folders_find_file(PDO $db, int $fileId): ?array
{
    $st = $db->prepare("SELECT
            f.id,
            f.folder_id,
            f.member_id,
            f.nome_original,
            f.nome_exibicao,
            f.storage_path,
            f.mime_type,
            f.ext,
            f.tamanho_bytes,
            f.status,
            f.created_at,
            f.updated_at,
            mf.status AS folder_status
        FROM member_files f
        INNER JOIN member_folders mf ON mf.id = f.folder_id
        WHERE f.id = ?
        LIMIT 1");
    $st->execute([$fileId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function member_folders_normalize_name(string $name, int $max = 255): string
{
    $collapsed = preg_replace('/\s+/', ' ', $name);
    $name = trim($collapsed === null ? $name : $collapsed);
    if ($name === '') {
        return '';
    }
    if (strlen($name) > $max) {
        return trim(substr($name, 0, $max));
    }
    return $name;
}

function member_folders_member_node_name(int $memberId, string $memberName): string
{
    $name = member_folders_normalize_name($memberName, 220);
    if ($name === '') {
        $name = 'Associado';
    }
    return $name . ' (#' . $memberId . ')';
}

function member_folders_list_options(array $input): array
{
    $q = member_folders_normalize_name((string) ($input['q'] ?? ''), 120);

    $page = (int) ($input['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = (int) ($input['per_page'] ?? 20);
    if ($perPage < 5) {
        $perPage = 5;
    }
    if ($perPage > 100) {
        $perPage = 100;
    }

    return [
        'q' => $q,
        'page' => $page,
        'per_page' => $perPage,
    ];
}

function member_folders_ensure_root(PDO $db, int $userId): array
{
    $st = $db->query("SELECT id, member_id, parent_id, tipo, nome, status, created_at, updated_at
        FROM member_folders
        WHERE parent_id IS NULL AND tipo = 'root' AND status = 'active'
        ORDER BY id ASC
        LIMIT 1");
    $root = $st->fetch(PDO::FETCH_ASSOC);
    if ($root) {
        return $root;
    }

    $ins = $db->prepare("INSERT INTO member_folders
        (member_id, parent_id, tipo, nome, status, created_by)
        VALUES (NULL, NULL, 'root', 'Pastas de Associados', 'active', ?)");
    $ins->execute([$userId > 0 ? $userId : null]);
    $id = (int) $db->lastInsertId();

    $created = member_folders_find_folder($db, $id);
    if (!$created) {
        throw new RuntimeException('Falha ao criar pasta raiz de associados');
    }
    return $created;
}

function member_folders_sync_member_nodes(PDO $db, int $rootId, int $userId): void
{
    $members = $db->query("SELECT id, nome
        FROM members
        WHERE status = 'ATIVO'
        ORDER BY nome ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($members)) {
        return;
    }

    $stExisting = $db->prepare("SELECT id, member_id, nome
        FROM member_folders
        WHERE parent_id = ? AND tipo = 'member' AND status = 'active'");
    $stExisting->execute([$rootId]);
    $existingRows = $stExisting->fetchAll(PDO::FETCH_ASSOC);

    $existingByMember = [];
    foreach ($existingRows as $row) {
        $memberId = (int) ($row['member_id'] ?? 0);
        if ($memberId > 0) {
            $existingByMember[$memberId] = $row;
        }
    }

    $insert = $db->prepare("INSERT INTO member_folders
        (member_id, parent_id, tipo, nome, status, created_by)
        VALUES (?, ?, 'member', ?, 'active', ?)");
    $update = $db->prepare("UPDATE member_folders SET nome = ?, updated_at = NOW() WHERE id = ?");

    foreach ($members as $member) {
        $memberId = (int) ($member['id'] ?? 0);
        if ($memberId <= 0) {
            continue;
        }

        $name = member_folders_member_node_name($memberId, (string) ($member['nome'] ?? ''));

        if (isset($existingByMember[$memberId])) {
            $row = $existingByMember[$memberId];
            if (member_folders_normalize_name((string) ($row['nome'] ?? '')) !== $name) {
                $update->execute([$name, (int) $row['id']]);
            }
            continue;
        }

        $insert->execute([$memberId, $rootId, $name, $userId > 0 ? $userId : null]);
    }
}

function member_folders_list_tree(PDO $db): array
{
    $rows = $db->query("SELECT
            f.id,
            f.parent_id,
            f.member_id,
            f.nome,
            f.tipo,
            f.updated_at,
            m.nome AS member_nome,
            m.status AS member_status
        FROM member_folders f
        LEFT JOIN members m ON m.id = f.member_id
        WHERE f.status = 'active'
        ORDER BY (f.tipo = 'root') DESC, f.nome ASC, f.id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'member_id' => $row['member_id'] !== null ? (int) $row['member_id'] : null,
            'nome' => (string) ($row['nome'] ?? ''),
            'tipo' => (string) ($row['tipo'] ?? 'folder'),
            'member_nome' => $row['member_nome'] !== null ? (string) $row['member_nome'] : null,
            'member_status' => $row['member_status'] !== null ? (string) $row['member_status'] : null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    return $out;
}

function member_folders_list_items(PDO $db, int $folderId, array $options = []): array
{
    $folder = member_folders_find_folder($db, $folderId);
    if (!$folder || (string) ($folder['status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Pasta nao encontrada', 404);
    }

    $opts = member_folders_list_options($options);
    $q = (string) ($opts['q'] ?? '');
    $page = (int) ($opts['page'] ?? 1);
    $perPage = (int) ($opts['per_page'] ?? 20);
    $offset = ($page - 1) * $perPage;

    $folderWhere = 'f.parent_id = ? AND f.status = \'active\'';
    $folderParams = [$folderId];
    if ($q !== '') {
        $folderWhere .= ' AND f.nome LIKE ?';
        $folderParams[] = '%' . $q . '%';
    }

    $stFolders = $db->prepare("SELECT
            f.id,
            f.parent_id,
            f.member_id,
            f.nome,
            f.tipo,
            f.created_at,
            f.updated_at,
            m.nome AS member_nome
        FROM member_folders f
        LEFT JOIN members m ON m.id = f.member_id
        WHERE {$folderWhere}
        ORDER BY f.nome ASC, f.id ASC");
    $stFolders->execute($folderParams);
    $folderRows = $stFolders->fetchAll(PDO::FETCH_ASSOC);

    $filesWhere = 'folder_id = ? AND status = \'active\'';
    $filesParams = [$folderId];
    if ($q !== '') {
        $filesWhere .= ' AND (nome_exibicao LIKE ? OR nome_original LIKE ?)';
        $filesParams[] = '%' . $q . '%';
        $filesParams[] = '%' . $q . '%';
    }

    $stFilesCount = $db->prepare("SELECT COUNT(*)
        FROM member_files
        WHERE {$filesWhere}");
    $stFilesCount->execute($filesParams);
    $filesTotal = (int) $stFilesCount->fetchColumn();
    $filesTotalPages = max(1, (int) ceil($filesTotal / $perPage));
    if ($page > $filesTotalPages) {
        $page = $filesTotalPages;
        $offset = ($page - 1) * $perPage;
    }

    $stFiles = $db->prepare("SELECT
            id,
            folder_id,
            member_id,
            nome_original,
            nome_exibicao,
            mime_type,
            ext,
            tamanho_bytes,
            created_at,
            updated_at
        FROM member_files
        WHERE {$filesWhere}
        ORDER BY nome_exibicao ASC, id ASC
        LIMIT {$perPage} OFFSET {$offset}");
    $stFiles->execute($filesParams);
    $fileRows = $stFiles->fetchAll(PDO::FETCH_ASSOC);

    $folders = [];
    foreach ($folderRows as $row) {
        $folders[] = [
            'id' => (int) $row['id'],
            'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'member_id' => $row['member_id'] !== null ? (int) $row['member_id'] : null,
            'nome' => (string) ($row['nome'] ?? ''),
            'tipo' => (string) ($row['tipo'] ?? 'folder'),
            'member_nome' => $row['member_nome'] !== null ? (string) $row['member_nome'] : null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    $files = [];
    foreach ($fileRows as $row) {
        $fileId = (int) $row['id'];
        $files[] = [
            'id' => $fileId,
            'folder_id' => (int) $row['folder_id'],
            'member_id' => $row['member_id'] !== null ? (int) $row['member_id'] : null,
            'nome_original' => (string) ($row['nome_original'] ?? ''),
            'nome_exibicao' => (string) ($row['nome_exibicao'] ?? ''),
            'mime_type' => (string) ($row['mime_type'] ?? ''),
            'ext' => (string) ($row['ext'] ?? ''),
            'tamanho_bytes' => (int) ($row['tamanho_bytes'] ?? 0),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'download_url' => member_folders_download_url($fileId),
        ];
    }

    return [
        'pasta_atual' => [
            'id' => (int) $folder['id'],
            'member_id' => $folder['member_id'] !== null ? (int) $folder['member_id'] : null,
            'parent_id' => $folder['parent_id'] !== null ? (int) $folder['parent_id'] : null,
            'tipo' => (string) ($folder['tipo'] ?? 'folder'),
            'nome' => (string) ($folder['nome'] ?? ''),
        ],
        'pastas' => $folders,
        'arquivos' => $files,
        'meta' => [
            'q' => $q,
            'files' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filesTotal,
                'total_pages' => $filesTotalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $filesTotalPages,
            ],
            'folders' => [
                'total' => count($folders),
            ],
        ],
    ];
}

function member_folders_folder_name_exists(PDO $db, int $parentId, string $name, int $excludeId = 0): bool
{
    $sql = "SELECT id
        FROM member_folders
        WHERE parent_id = ?
          AND nome = ?
          AND status = 'active'";
    $params = [$parentId, $name];

    if ($excludeId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $st = $db->prepare($sql);
    $st->execute($params);
    return (bool) $st->fetch(PDO::FETCH_ASSOC);
}

function member_folders_create_folder(PDO $db, int $parentId, string $name, int $userId): array
{
    $parent = member_folders_find_folder($db, $parentId);
    if (!$parent || (string) ($parent['status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Pasta pai nao encontrada', 404);
    }
    $parentType = (string) ($parent['tipo'] ?? '');
    if (!in_array($parentType, ['root', 'member', 'folder'], true)) {
        anateje_error('VALIDATION', 'Nova pasta permitida apenas dentro da arvore de pastas de associados', 422);
    }

    $name = member_folders_normalize_name($name);
    if ($name === '') {
        anateje_error('VALIDATION', 'Nome da pasta e obrigatorio', 422);
    }
    if (member_folders_folder_name_exists($db, $parentId, $name)) {
        anateje_error('VALIDATION', 'Ja existe uma pasta com este nome no mesmo nivel', 422);
    }

    $memberId = $parent['member_id'] !== null ? (int) $parent['member_id'] : null;

    $st = $db->prepare("INSERT INTO member_folders
        (member_id, parent_id, tipo, nome, status, created_by)
        VALUES (?, ?, 'folder', ?, 'active', ?)");
    $st->execute([
        $memberId,
        $parentId,
        $name,
        $userId > 0 ? $userId : null
    ]);

    $id = (int) $db->lastInsertId();
    $created = member_folders_find_folder($db, $id);
    if (!$created) {
        throw new RuntimeException('Falha ao criar pasta');
    }

    anateje_audit_log(
        $db,
        $userId,
        'admin.pastas_associados',
        'create_folder',
        'member_folders',
        $id,
        null,
        $created,
        ['parent_id' => $parentId]
    );

    return $created;
}

function member_folders_rename_folder(PDO $db, int $folderId, string $name, int $userId): array
{
    $folder = member_folders_find_folder($db, $folderId);
    if (!$folder || (string) ($folder['status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Pasta nao encontrada', 404);
    }

    $tipo = (string) ($folder['tipo'] ?? '');
    if ($tipo === 'root' || $tipo === 'member') {
        anateje_error('VALIDATION', 'Esta pasta nao pode ser renomeada', 422);
    }

    $name = member_folders_normalize_name($name);
    if ($name === '') {
        anateje_error('VALIDATION', 'Nome da pasta e obrigatorio', 422);
    }

    $parentId = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : 0;
    if ($parentId <= 0) {
        anateje_error('VALIDATION', 'Pasta sem parent valido', 422);
    }
    if (member_folders_folder_name_exists($db, $parentId, $name, $folderId)) {
        anateje_error('VALIDATION', 'Ja existe uma pasta com este nome no mesmo nivel', 422);
    }

    $before = $folder;
    $st = $db->prepare('UPDATE member_folders SET nome = ?, updated_at = NOW() WHERE id = ?');
    $st->execute([$name, $folderId]);

    $after = member_folders_find_folder($db, $folderId);
    anateje_audit_log(
        $db,
        $userId,
        'admin.pastas_associados',
        'rename_folder',
        'member_folders',
        $folderId,
        $before,
        $after,
        []
    );

    return $after ?: $before;
}

function member_folders_soft_delete_folder_tree(PDO $db, int $folderId): void
{
    $stack = [$folderId];
    $stChildren = $db->prepare("SELECT id FROM member_folders WHERE parent_id = ? AND status = 'active'");
    $stDelFiles = $db->prepare("UPDATE member_files SET status = 'trash', updated_at = NOW() WHERE folder_id = ? AND status = 'active'");
    $stDelFolder = $db->prepare("UPDATE member_folders SET status = 'trash', updated_at = NOW() WHERE id = ?");

    while (!empty($stack)) {
        $current = (int) array_pop($stack);
        $stChildren->execute([$current]);
        $children = $stChildren->fetchAll(PDO::FETCH_ASSOC);
        foreach ($children as $child) {
            $childId = (int) ($child['id'] ?? 0);
            if ($childId > 0) {
                $stack[] = $childId;
            }
        }
        $stDelFiles->execute([$current]);
        $stDelFolder->execute([$current]);
    }
}

function member_folders_delete_folder(PDO $db, int $folderId, int $userId): void
{
    $folder = member_folders_find_folder($db, $folderId);
    if (!$folder || (string) ($folder['status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Pasta nao encontrada', 404);
    }

    $tipo = (string) ($folder['tipo'] ?? '');
    if ($tipo === 'root' || $tipo === 'member') {
        anateje_error('VALIDATION', 'Esta pasta nao pode ser excluida', 422);
    }

    $before = $folder;
    $db->beginTransaction();
    try {
        member_folders_soft_delete_folder_tree($db, $folderId);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    anateje_audit_log(
        $db,
        $userId,
        'admin.pastas_associados',
        'delete_folder',
        'member_folders',
        $folderId,
        $before,
        ['status' => 'trash'],
        []
    );
}

function member_folders_file_name_exists(PDO $db, int $folderId, string $name, int $excludeId = 0): bool
{
    $sql = "SELECT id
        FROM member_files
        WHERE folder_id = ?
          AND nome_exibicao = ?
          AND status = 'active'";
    $params = [$folderId, $name];

    if ($excludeId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }
    $sql .= ' LIMIT 1';

    $st = $db->prepare($sql);
    $st->execute($params);
    return (bool) $st->fetch(PDO::FETCH_ASSOC);
}

function member_folders_file_ext_from_name(string $name): string
{
    return strtolower(trim((string) pathinfo($name, PATHINFO_EXTENSION)));
}

function member_folders_detect_mime(string $tmpPath, string $ext): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if (is_string($mime) && trim($mime) !== '') {
                return trim($mime);
            }
        }
    }

    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($tmpPath);
        if (is_string($mime) && trim($mime) !== '') {
            return trim($mime);
        }
    }

    $fallback = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    return $fallback[$ext] ?? 'application/octet-stream';
}

function member_folders_upload_dir(int $memberId, int $folderId): array
{
    $memberDir = $memberId > 0 ? ('member_' . $memberId) : 'shared';
    $relative = 'member_folders/' . $memberDir . '/' . $folderId;
    $absolute = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return [$relative, $absolute];
}

function member_folders_upload_file(PDO $db, int $folderId, array $file, ?string $displayName, int $userId): array
{
    $folder = member_folders_find_folder($db, $folderId);
    if (!$folder || (string) ($folder['status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Pasta nao encontrada', 404);
    }

    $folderType = (string) ($folder['tipo'] ?? '');
    if (!in_array($folderType, ['member', 'folder'], true)) {
        anateje_error('VALIDATION', 'Upload permitido apenas em pasta de associado', 422);
    }

    if (!isset($file['tmp_name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        anateje_error('VALIDATION', 'Arquivo nao enviado corretamente', 422);
    }

    $maxSize = 25 * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        anateje_error('VALIDATION', 'Arquivo vazio', 422);
    }
    if ($size > $maxSize) {
        anateje_error('VALIDATION', 'Arquivo excede o limite de 25MB', 422);
    }

    $original = member_folders_normalize_name((string) ($file['name'] ?? ''), 255);
    if ($original === '') {
        $original = 'arquivo';
    }
    $ext = member_folders_file_ext_from_name($original);
    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowedExt, true)) {
        anateje_error('VALIDATION', 'Extensao nao permitida', 422);
    }

    $mime = member_folders_detect_mime((string) $file['tmp_name'], $ext);
    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'application/octet-stream',
    ];
    if (!in_array($mime, $allowedMime, true)) {
        anateje_error('VALIDATION', 'Tipo de arquivo nao permitido', 422);
    }

    $name = member_folders_normalize_name((string) ($displayName ?? ''), 255);
    if ($name === '') {
        $name = $original;
    }

    if (member_folders_file_name_exists($db, $folderId, $name)) {
        anateje_error('VALIDATION', 'Ja existe arquivo com este nome na pasta', 422);
    }

    $memberId = (int) ($folder['member_id'] ?? 0);
    [$relativeDir, $absoluteDir] = member_folders_upload_dir($memberId, $folderId);
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        throw new RuntimeException('Falha ao criar diretorio de upload');
    }

    $storedName = uniqid('member_file_', true) . '.' . $ext;
    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
        anateje_error('FAIL', 'Falha ao salvar arquivo', 500);
    }

    $storagePath = $relativeDir . '/' . $storedName;
    $st = $db->prepare("INSERT INTO member_files
        (folder_id, member_id, nome_original, nome_exibicao, storage_path, mime_type, ext, tamanho_bytes, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
    $st->execute([
        $folderId,
        $memberId > 0 ? $memberId : null,
        $original,
        $name,
        $storagePath,
        $mime,
        $ext,
        $size,
        $userId > 0 ? $userId : null,
    ]);

    $id = (int) $db->lastInsertId();
    $created = member_folders_find_file($db, $id);
    anateje_audit_log(
        $db,
        $userId,
        'admin.pastas_associados',
        'upload_file',
        'member_files',
        $id,
        null,
        [
            'folder_id' => $folderId,
            'member_id' => $memberId > 0 ? $memberId : null,
            'nome_exibicao' => $name,
            'nome_original' => $original,
            'storage_path' => $storagePath,
            'ext' => $ext,
            'size' => $size,
        ],
        []
    );

    return [
        'id' => $id,
        'folder_id' => $folderId,
        'member_id' => $memberId > 0 ? $memberId : null,
        'nome_original' => $original,
        'nome_exibicao' => $name,
        'mime_type' => $mime,
        'ext' => $ext,
        'tamanho_bytes' => $size,
        'download_url' => member_folders_download_url($id),
        'created_at' => $created['created_at'] ?? null,
        'updated_at' => $created['updated_at'] ?? null,
    ];
}

function member_folders_rename_file(PDO $db, int $fileId, string $name, int $userId): array
{
    $file = member_folders_find_file($db, $fileId);
    if (!$file || (string) ($file['status'] ?? '') !== 'active' || (string) ($file['folder_status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Arquivo nao encontrado', 404);
    }

    $name = member_folders_normalize_name($name, 255);
    if ($name === '') {
        anateje_error('VALIDATION', 'Nome do arquivo e obrigatorio', 422);
    }

    $folderId = (int) ($file['folder_id'] ?? 0);
    if ($folderId <= 0) {
        anateje_error('VALIDATION', 'Pasta do arquivo invalida', 422);
    }
    if (member_folders_file_name_exists($db, $folderId, $name, $fileId)) {
        anateje_error('VALIDATION', 'Ja existe arquivo com este nome na pasta', 422);
    }

    $before = $file;
    $st = $db->prepare('UPDATE member_files SET nome_exibicao = ?, updated_at = NOW() WHERE id = ?');
    $st->execute([$name, $fileId]);
    $after = member_folders_find_file($db, $fileId);

    anateje_audit_log(
        $db,
        $userId,
        'admin.pastas_associados',
        'rename_file',
        'member_files',
        $fileId,
        $before,
        $after,
        []
    );

    return [
        'id' => $fileId,
        'folder_id' => (int) ($after['folder_id'] ?? $folderId),
        'member_id' => isset($after['member_id']) ? (int) $after['member_id'] : null,
        'nome_original' => (string) ($after['nome_original'] ?? $file['nome_original']),
        'nome_exibicao' => (string) ($after['nome_exibicao'] ?? $name),
        'mime_type' => (string) ($after['mime_type'] ?? $file['mime_type']),
        'ext' => (string) ($after['ext'] ?? $file['ext']),
        'tamanho_bytes' => (int) ($after['tamanho_bytes'] ?? $file['tamanho_bytes']),
        'download_url' => member_folders_download_url($fileId),
        'created_at' => $after['created_at'] ?? $file['created_at'],
        'updated_at' => $after['updated_at'] ?? $file['updated_at'],
    ];
}

function member_folders_delete_file(PDO $db, int $fileId, int $userId): void
{
    $file = member_folders_find_file($db, $fileId);
    if (!$file || (string) ($file['status'] ?? '') !== 'active' || (string) ($file['folder_status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Arquivo nao encontrado', 404);
    }

    $before = $file;
    $st = $db->prepare("UPDATE member_files SET status = 'trash', updated_at = NOW() WHERE id = ?");
    $st->execute([$fileId]);

    anateje_audit_log(
        $db,
        $userId,
        'admin.pastas_associados',
        'delete_file',
        'member_files',
        $fileId,
        $before,
        ['status' => 'trash'],
        []
    );
}

function member_folders_is_descendant(PDO $db, int $targetParentId, int $folderId): bool
{
    $cursor = $targetParentId;
    $st = $db->prepare('SELECT parent_id FROM member_folders WHERE id = ? LIMIT 1');

    while ($cursor > 0) {
        if ($cursor === $folderId) {
            return true;
        }
        $st->execute([$cursor]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['parent_id'] === null) {
            return false;
        }
        $cursor = (int) $row['parent_id'];
    }

    return false;
}

function member_folders_move_item(PDO $db, string $itemType, int $itemId, int $targetFolderId, int $userId): void
{
    $target = member_folders_find_folder($db, $targetFolderId);
    if (!$target || (string) ($target['status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Pasta de destino nao encontrada', 404);
    }

    $targetType = (string) ($target['tipo'] ?? '');
    if (!in_array($targetType, ['member', 'folder'], true)) {
        anateje_error('VALIDATION', 'Destino invalido para mover item', 422);
    }

    if ($itemType === 'folder') {
        $folder = member_folders_find_folder($db, $itemId);
        if (!$folder || (string) ($folder['status'] ?? '') !== 'active') {
            anateje_error('NOT_FOUND', 'Pasta nao encontrada', 404);
        }

        $folderType = (string) ($folder['tipo'] ?? '');
        if ($folderType !== 'folder') {
            anateje_error('VALIDATION', 'Apenas pastas comuns podem ser movidas', 422);
        }
        if ((int) ($folder['parent_id'] ?? 0) === $targetFolderId) {
            anateje_error('VALIDATION', 'A pasta ja esta neste destino', 422);
        }
        if ((int) $folder['id'] === $targetFolderId) {
            anateje_error('VALIDATION', 'Destino invalido', 422);
        }
        if (member_folders_is_descendant($db, $targetFolderId, (int) $folder['id'])) {
            anateje_error('VALIDATION', 'Nao e permitido mover pasta para dentro dela mesma', 422);
        }

        $sourceMember = (int) ($folder['member_id'] ?? 0);
        $targetMember = (int) ($target['member_id'] ?? 0);
        if ($sourceMember !== $targetMember) {
            anateje_error('VALIDATION', 'Movimento entre associados diferentes nao permitido nesta fase', 422);
        }

        $name = member_folders_normalize_name((string) ($folder['nome'] ?? ''));
        if (member_folders_folder_name_exists($db, $targetFolderId, $name, (int) $folder['id'])) {
            anateje_error('VALIDATION', 'Ja existe pasta com este nome no destino', 422);
        }

        $before = $folder;
        $st = $db->prepare('UPDATE member_folders SET parent_id = ?, updated_at = NOW() WHERE id = ?');
        $st->execute([$targetFolderId, $itemId]);
        $after = member_folders_find_folder($db, $itemId);

        anateje_audit_log(
            $db,
            $userId,
            'admin.pastas_associados',
            'move_folder',
            'member_folders',
            $itemId,
            $before,
            $after,
            ['target_folder_id' => $targetFolderId]
        );
        return;
    }

    if ($itemType === 'file') {
        $file = member_folders_find_file($db, $itemId);
        if (!$file || (string) ($file['status'] ?? '') !== 'active' || (string) ($file['folder_status'] ?? '') !== 'active') {
            anateje_error('NOT_FOUND', 'Arquivo nao encontrado', 404);
        }
        if ((int) ($file['folder_id'] ?? 0) === $targetFolderId) {
            anateje_error('VALIDATION', 'O arquivo ja esta neste destino', 422);
        }

        $sourceMember = (int) ($file['member_id'] ?? 0);
        $targetMember = (int) ($target['member_id'] ?? 0);
        if ($sourceMember !== $targetMember) {
            anateje_error('VALIDATION', 'Movimento entre associados diferentes nao permitido nesta fase', 422);
        }

        $name = member_folders_normalize_name((string) ($file['nome_exibicao'] ?? ''), 255);
        if (member_folders_file_name_exists($db, $targetFolderId, $name, $itemId)) {
            anateje_error('VALIDATION', 'Ja existe arquivo com este nome no destino', 422);
        }

        $before = $file;
        $st = $db->prepare('UPDATE member_files SET folder_id = ?, updated_at = NOW() WHERE id = ?');
        $st->execute([$targetFolderId, $itemId]);
        $after = member_folders_find_file($db, $itemId);

        anateje_audit_log(
            $db,
            $userId,
            'admin.pastas_associados',
            'move_file',
            'member_files',
            $itemId,
            $before,
            $after,
            ['target_folder_id' => $targetFolderId]
        );
        return;
    }

    anateje_error('VALIDATION', 'Tipo de item invalido', 422);
}

function member_folders_copy_file_display_name(PDO $db, int $folderId, string $baseName): string
{
    $baseName = member_folders_normalize_name($baseName, 255);
    if ($baseName === '') {
        $baseName = 'arquivo';
    }

    $info = pathinfo($baseName);
    $filename = member_folders_normalize_name((string) ($info['filename'] ?? 'arquivo'), 200);
    if ($filename === '') {
        $filename = 'arquivo';
    }
    $extension = member_folders_normalize_name((string) ($info['extension'] ?? ''), 20);

    $candidate = $extension !== ''
        ? ($filename . ' (copia).' . $extension)
        : ($filename . ' (copia)');
    $counter = 2;

    while (member_folders_file_name_exists($db, $folderId, $candidate)) {
        $candidate = $extension !== ''
            ? ($filename . ' (copia ' . $counter . ').' . $extension)
            : ($filename . ' (copia ' . $counter . ')');
        $counter++;
    }

    return $candidate;
}

function member_folders_copy_file(PDO $db, int $fileId, int $targetFolderId, int $userId): array
{
    $file = member_folders_find_file($db, $fileId);
    if (!$file || (string) ($file['status'] ?? '') !== 'active' || (string) ($file['folder_status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Arquivo nao encontrado', 404);
    }

    $target = member_folders_find_folder($db, $targetFolderId);
    if (!$target || (string) ($target['status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Pasta de destino nao encontrada', 404);
    }
    $targetType = (string) ($target['tipo'] ?? '');
    if (!in_array($targetType, ['member', 'folder'], true)) {
        anateje_error('VALIDATION', 'Destino invalido para copia', 422);
    }

    $sourceMember = (int) ($file['member_id'] ?? 0);
    $targetMember = (int) ($target['member_id'] ?? 0);
    if ($sourceMember !== $targetMember) {
        anateje_error('VALIDATION', 'Copia entre associados diferentes nao permitida nesta fase', 422);
    }

    $sourcePath = trim((string) ($file['storage_path'] ?? ''));
    if ($sourcePath === '') {
        anateje_error('NOT_FOUND', 'Arquivo sem caminho de armazenamento', 404);
    }

    $absoluteSource = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sourcePath);
    if (!is_file($absoluteSource)) {
        anateje_error('NOT_FOUND', 'Arquivo fisico nao encontrado', 404);
    }

    $displayName = member_folders_copy_file_display_name($db, $targetFolderId, (string) ($file['nome_exibicao'] ?? 'arquivo'));
    $ext = member_folders_file_ext_from_name((string) ($file['nome_original'] ?? ''));
    if ($ext === '') {
        $ext = member_folders_normalize_name((string) ($file['ext'] ?? ''), 20);
    }
    $storedName = uniqid('member_file_', true) . ($ext !== '' ? ('.' . $ext) : '');

    [$relativeDir, $absoluteDir] = member_folders_upload_dir($targetMember, $targetFolderId);
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        throw new RuntimeException('Falha ao criar diretorio de destino para copia');
    }

    $absoluteTarget = $absoluteDir . DIRECTORY_SEPARATOR . $storedName;
    if (!copy($absoluteSource, $absoluteTarget)) {
        anateje_error('FAIL', 'Falha ao copiar arquivo', 500);
    }

    $storagePath = $relativeDir . '/' . $storedName;
    $st = $db->prepare("INSERT INTO member_files
        (folder_id, member_id, nome_original, nome_exibicao, storage_path, mime_type, ext, tamanho_bytes, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
    $st->execute([
        $targetFolderId,
        $targetMember > 0 ? $targetMember : null,
        (string) ($file['nome_original'] ?? 'arquivo'),
        $displayName,
        $storagePath,
        (string) ($file['mime_type'] ?? 'application/octet-stream'),
        (string) ($file['ext'] ?? $ext),
        (int) ($file['tamanho_bytes'] ?? 0),
        $userId > 0 ? $userId : null,
    ]);

    $newId = (int) $db->lastInsertId();
    $created = member_folders_find_file($db, $newId);
    anateje_audit_log(
        $db,
        $userId,
        'admin.pastas_associados',
        'copy_file',
        'member_files',
        $newId,
        $file,
        $created,
        ['source_id' => $fileId, 'target_folder_id' => $targetFolderId]
    );

    return [
        'id' => $newId,
        'folder_id' => $targetFolderId,
        'member_id' => $targetMember > 0 ? $targetMember : null,
        'nome_original' => (string) ($file['nome_original'] ?? 'arquivo'),
        'nome_exibicao' => $displayName,
        'mime_type' => (string) ($file['mime_type'] ?? 'application/octet-stream'),
        'ext' => (string) ($file['ext'] ?? $ext),
        'tamanho_bytes' => (int) ($file['tamanho_bytes'] ?? 0),
        'download_url' => member_folders_download_url($newId),
        'created_at' => $created['created_at'] ?? null,
        'updated_at' => $created['updated_at'] ?? null,
    ];
}

function member_folders_download(PDO $db, int $fileId): void
{
    $file = member_folders_find_file($db, $fileId);
    if (!$file || (string) ($file['status'] ?? '') !== 'active' || (string) ($file['folder_status'] ?? '') !== 'active') {
        anateje_error('NOT_FOUND', 'Arquivo nao encontrado', 404);
    }

    $storagePath = trim((string) ($file['storage_path'] ?? ''));
    if ($storagePath === '') {
        anateje_error('NOT_FOUND', 'Arquivo sem caminho de armazenamento', 404);
    }

    $base = rtrim((string) UPLOAD_PATH, '/\\');
    $absolutePath = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storagePath);
    if (!is_file($absolutePath)) {
        anateje_error('NOT_FOUND', 'Arquivo fisico nao encontrado', 404);
    }

    $downloadName = trim((string) ($file['nome_original'] ?: $file['nome_exibicao']));
    if ($downloadName === '') {
        $downloadName = 'arquivo-' . $fileId;
    }
    $downloadName = preg_replace('/[^a-zA-Z0-9._ -]+/', '_', basename($downloadName));

    if (ob_get_level() > 0) {
        ob_clean();
    }
    header('Content-Type: ' . ((string) ($file['mime_type'] ?? '') !== '' ? $file['mime_type'] : 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($absolutePath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    readfile($absolutePath);
    exit;
}

function member_folders_file_counts(PDO $db): array
{
    $rows = $db->query("SELECT f.folder_id, COUNT(*) AS total
        FROM member_files f
        INNER JOIN member_folders mf ON mf.id = f.folder_id
        WHERE f.status = 'active'
          AND mf.status = 'active'
        GROUP BY f.folder_id")->fetchAll(PDO::FETCH_ASSOC);

    $counts = [];
    foreach ($rows as $row) {
        $folderId = (int) ($row['folder_id'] ?? 0);
        if ($folderId <= 0) {
            continue;
        }
        $counts[(string) $folderId] = (int) ($row['total'] ?? 0);
    }

    return $counts;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$userId = member_folders_now_user_id($auth);

try {
    if ($method === 'GET') {
        $action = trim((string) ($_GET['action'] ?? 'admin_init'));
        if ($action === 'init') {
            $action = 'admin_init';
        } elseif ($action === 'download') {
            $action = 'admin_download';
        }

        if ($action === 'admin_init') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.view');
            $root = member_folders_ensure_root($db, $userId);
            member_folders_sync_member_nodes($db, (int) $root['id'], $userId);
            $listOptions = member_folders_list_options($_GET);
            anateje_ok([
                'unidade_id' => 1,
                'root' => [
                    'id' => (int) $root['id'],
                    'member_id' => null,
                    'parent_id' => null,
                    'tipo' => 'root',
                    'nome' => (string) $root['nome'],
                ],
                'tree' => member_folders_list_tree($db),
                'itens' => member_folders_list_items($db, (int) $root['id'], $listOptions),
            ]);
        }

        if ($action === 'listar_arvore') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.view');
            $root = member_folders_ensure_root($db, $userId);
            member_folders_sync_member_nodes($db, (int) $root['id'], $userId);
            anateje_ok(member_folders_list_tree($db));
        }

        if ($action === 'contadores_arquivos') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.view');
            $root = member_folders_ensure_root($db, $userId);
            member_folders_sync_member_nodes($db, (int) $root['id'], $userId);
            anateje_ok(['counts' => member_folders_file_counts($db)]);
        }

        if ($action === 'admin_tree') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.view');
            $root = member_folders_ensure_root($db, $userId);
            member_folders_sync_member_nodes($db, (int) $root['id'], $userId);
            anateje_ok([
                'root_id' => (int) $root['id'],
                'tree' => member_folders_list_tree($db),
            ]);
        }

        if ($action === 'admin_items' || $action === 'listar_itens') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.view');
            $folderId = (int) ($_GET['folder_id'] ?? ($_GET['pasta_id'] ?? 0));
            if ($folderId <= 0) {
                $root = member_folders_ensure_root($db, $userId);
                $folderId = (int) $root['id'];
            }
            $listOptions = member_folders_list_options($_GET);
            anateje_ok(member_folders_list_items($db, $folderId, $listOptions));
        }

        if ($action === 'admin_download') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.download');
            $fileId = (int) ($_GET['id'] ?? 0);
            if ($fileId <= 0) {
                anateje_error('VALIDATION', 'ID de arquivo invalido', 422);
            }
            member_folders_download($db, $fileId);
        }

        anateje_error('NOT_FOUND', 'Acao invalida', 404);
    }

    if ($method === 'POST') {
        $input = member_folders_request_input();
        $action = trim((string) ($_GET['action'] ?? ($input['action'] ?? '')));
        if ($action === 'criar_pasta') {
            $action = 'admin_create_folder';
        } elseif ($action === 'renomear_pasta') {
            $action = 'admin_rename_folder';
        } elseif ($action === 'renomear_arquivo') {
            $action = 'admin_rename_file';
        } elseif ($action === 'upload_arquivo') {
            $action = 'admin_upload_file';
            if (!isset($input['folder_id']) && isset($input['pasta_id'])) {
                $input['folder_id'] = $input['pasta_id'];
            }
        } elseif ($action === 'mover_item') {
            $action = 'admin_move_item';
            if (!isset($input['item_type']) && isset($input['tipo'])) {
                $input['item_type'] = $input['tipo'];
            }
            if (!isset($input['item_id']) && isset($input['id'])) {
                $input['item_id'] = $input['id'];
            }
            if (!isset($input['target_folder_id']) && isset($input['novo_parent_id'])) {
                $input['target_folder_id'] = $input['novo_parent_id'];
            }
            if (isset($input['item_type'])) {
                $type = strtolower(trim((string) $input['item_type']));
                if ($type === 'pasta') {
                    $input['item_type'] = 'folder';
                } elseif ($type === 'arquivo') {
                    $input['item_type'] = 'file';
                }
            }
        } elseif ($action === 'copiar_arquivo') {
            $action = 'admin_copy_file';
            if (!isset($input['file_id']) && isset($input['id'])) {
                $input['file_id'] = $input['id'];
            }
            if (!isset($input['target_folder_id']) && isset($input['pasta_destino_id'])) {
                $input['target_folder_id'] = $input['pasta_destino_id'];
            }
        }

        if ($action === 'excluir_item') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.delete');
            $tipo = strtolower(trim((string) ($input['tipo'] ?? '')));
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                anateje_error('VALIDATION', 'ID invalido para exclusao', 422);
            }
            if ($tipo === 'pasta' || $tipo === 'folder') {
                member_folders_delete_folder($db, $id, $userId);
                anateje_ok(['deleted' => true]);
            }
            if ($tipo === 'arquivo' || $tipo === 'file') {
                member_folders_delete_file($db, $id, $userId);
                anateje_ok(['deleted' => true]);
            }
            anateje_error('VALIDATION', 'Tipo invalido para exclusao', 422);
        }

        if ($action === 'admin_create_folder') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.create');
            $parentId = (int) ($input['parent_id'] ?? 0);
            if ($parentId <= 0) {
                anateje_error('VALIDATION', 'Pasta pai invalida', 422);
            }
            $name = (string) ($input['nome'] ?? '');
            $created = member_folders_create_folder($db, $parentId, $name, $userId);
            anateje_ok(['folder' => $created]);
        }

        if ($action === 'admin_rename_folder') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.edit');
            $folderId = (int) ($input['id'] ?? 0);
            if ($folderId <= 0) {
                anateje_error('VALIDATION', 'Pasta invalida', 422);
            }
            $name = (string) ($input['nome'] ?? '');
            $renamed = member_folders_rename_folder($db, $folderId, $name, $userId);
            anateje_ok(['folder' => $renamed]);
        }

        if ($action === 'admin_delete_folder') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.delete');
            $folderId = (int) ($input['id'] ?? 0);
            if ($folderId <= 0) {
                anateje_error('VALIDATION', 'Pasta invalida', 422);
            }
            member_folders_delete_folder($db, $folderId, $userId);
            anateje_ok(['deleted' => true]);
        }

        if ($action === 'admin_upload_file') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.upload');
            $folderId = (int) ($input['folder_id'] ?? ($_POST['folder_id'] ?? 0));
            if ($folderId <= 0) {
                anateje_error('VALIDATION', 'Pasta de destino invalida', 422);
            }
            $displayName = isset($input['nome_exibicao']) ? (string) $input['nome_exibicao'] : null;
            $uploaded = member_folders_upload_file($db, $folderId, $_FILES['arquivo'] ?? [], $displayName, $userId);
            anateje_ok(['file' => $uploaded]);
        }

        if ($action === 'admin_rename_file') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.edit');
            $fileId = (int) ($input['id'] ?? 0);
            if ($fileId <= 0) {
                anateje_error('VALIDATION', 'Arquivo invalido', 422);
            }
            $name = (string) ($input['nome'] ?? '');
            $renamed = member_folders_rename_file($db, $fileId, $name, $userId);
            anateje_ok(['file' => $renamed]);
        }

        if ($action === 'admin_delete_file') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.delete');
            $fileId = (int) ($input['id'] ?? 0);
            if ($fileId <= 0) {
                anateje_error('VALIDATION', 'Arquivo invalido', 422);
            }
            member_folders_delete_file($db, $fileId, $userId);
            anateje_ok(['deleted' => true]);
        }

        if ($action === 'admin_move_item') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.edit');
            $itemType = strtolower(trim((string) ($input['item_type'] ?? '')));
            $itemId = (int) ($input['item_id'] ?? 0);
            $targetFolderId = (int) ($input['target_folder_id'] ?? 0);
            if (!in_array($itemType, ['folder', 'file'], true) || $itemId <= 0 || $targetFolderId <= 0) {
                anateje_error('VALIDATION', 'Dados invalidos para mover item', 422);
            }
            member_folders_move_item($db, $itemType, $itemId, $targetFolderId, $userId);
            anateje_ok(['moved' => true]);
        }

        if ($action === 'admin_copy_file') {
            anateje_require_permission($db, $auth, 'admin.pastas_associados.edit');
            $fileId = (int) ($input['file_id'] ?? 0);
            $targetFolderId = (int) ($input['target_folder_id'] ?? 0);
            if ($fileId <= 0 || $targetFolderId <= 0) {
                anateje_error('VALIDATION', 'Dados invalidos para copiar arquivo', 422);
            }
            $copied = member_folders_copy_file($db, $fileId, $targetFolderId, $userId);
            anateje_ok(['file' => $copied]);
        }

        anateje_error('NOT_FOUND', 'Acao invalida', 404);
    }

    anateje_error('METHOD_NOT_ALLOWED', 'Metodo nao permitido', 405);
} catch (Throwable $e) {
    $action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? 'unknown')));
    logError('Erro member_folders.' . $action . ': ' . $e->getMessage());
    anateje_error('FAIL', 'Falha ao processar pastas de associados', 500);
}
