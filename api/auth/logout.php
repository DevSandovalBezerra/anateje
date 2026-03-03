<?php
// ANATEJE - API de logout com limpeza segura de sessao

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paths.php';

function limparSessaoBanco($usuario_id)
{
    try {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM sessoes WHERE usuario_id = ?');
        $stmt->execute([(int) $usuario_id]);
        return true;
    } catch (Exception $e) {
        logError('Erro ao limpar sessao do banco: ' . $e->getMessage(), ['user_id' => (int) $usuario_id]);
        return false;
    }
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Metodo nao permitido'], 405);
}

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
if ($usuarioId > 0) {
    limparSessaoBanco($usuarioId);
}

destroyAuthSession();

jsonResponse([
    'success' => true,
    'message' => 'Logout realizado com sucesso'
]);
