<?php
// ANATEJE - ProteÃ§Ã£o das PÃ¡ginas Financeiras
// Sistema de Gestao Financeira Associativa ANATEJE

require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/base_path.php';

// Verificar se estÃ¡ logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['perfil_id'])) {
    $baseUrl = lidergest_base_url();
    $loginUrl = $baseUrl ? "{$baseUrl}/frontend/auth/login.html" : 'frontend/auth/login.html';
    header("Location: {$loginUrl}");
    exit;
}

// Determinar qual pÃ¡gina estÃ¡ sendo acessada
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Verificar permissÃ£o para a pÃ¡gina especÃ­fica
checkPermission('financeiro', $current_page);

// Se chegou atÃ© aqui, o usuÃ¡rio tem permissÃ£o
$user = [
    'id' => $_SESSION['user_id'],
    'nome' => $_SESSION['user_name'],
    'email' => $_SESSION['user_email'],
    'perfil_id' => $_SESSION['perfil_id'],
    'perfil_nome' => $_SESSION['perfil_nome']
];



