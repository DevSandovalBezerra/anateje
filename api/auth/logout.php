<?php
// LiderGest - API de Logout
// Sistema de Gestão Pedagógico-Financeira Líder School

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paths.php';

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para resposta JSON
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Função para limpar sessão do banco de dados
function limparSessaoBanco($usuario_id)
{
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM sessoes WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return true;
    } catch (Exception $e) {
        error_log("Erro ao limpar sessão do banco: " . $e->getMessage());
        return false;
    }
}

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Usuário não está logado'], 401);
}

// Processar logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['user_id'];

    // Limpar sessão do banco de dados
    limparSessaoBanco($usuario_id);

    // Destruir sessão PHP completamente
    session_unset();
    session_destroy();

    // Limpar cookie de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }

    // Resposta de sucesso
    $redirectUrl = '/lidergest/index.php';
    jsonResponse([
        'success' => true,
        'message' => 'Logout realizado com sucesso',
        'redirect' => $redirectUrl
    ]);
} else {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}
