<?php
// LiderGest - Sistema de Autenticação
// Sistema de Gestão Pedagógico-Financeira Líder School

require_once __DIR__ . '/../../config/database.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // Login do usuário
    public function login($email, $password)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.nome, u.email, u.senha, u.perfil_id, p.nome as perfil_nome, p.permissoes
                FROM usuarios u
                JOIN perfis_acesso p ON u.perfil_id = p.id
                WHERE u.email = ? AND u.ativo = 1
            ");

            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['senha'])) {
                return ['success' => false, 'message' => 'Email ou senha inválidos'];
            }

            // Atualizar último login
            $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Buscar unidade_id do usuário
            $unidade_id = null;
            if ($user['perfil_id'] != 1) { // Não é Admin Global
                // Verificar se é professor
                $stmt_unidade = $this->db->prepare("
                    SELECT unidade_id 
                    FROM professores 
                    WHERE usuario_id = ? AND ativo = 1
                    LIMIT 1
                ");
                $stmt_unidade->execute([$user['id']]);
                $professor = $stmt_unidade->fetch(PDO::FETCH_ASSOC);
                
                if ($professor && $professor['unidade_id']) {
                    $unidade_id = $professor['unidade_id'];
                } else {
                    // Verificar se tem unidade_id direto na tabela usuarios
                    $stmt_unidade = $this->db->prepare("
                        SELECT unidade_id 
                        FROM usuarios 
                        WHERE id = ? AND ativo = 1
                    ");
                    $stmt_unidade->execute([$user['id']]);
                    $usuario = $stmt_unidade->fetch(PDO::FETCH_ASSOC);
                    
                    if ($usuario && isset($usuario['unidade_id']) && $usuario['unidade_id']) {
                        $unidade_id = $usuario['unidade_id'];
                    }
                }
            }

            // Criar sessão
            $token = generateToken($user['id'], $user['perfil_id']);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['perfil_id'] = $user['perfil_id'];
            $_SESSION['perfil_nome'] = $user['perfil_nome'];
            $_SESSION['permissoes'] = json_decode($user['permissoes'], true);
            $_SESSION['token'] = $token;
            if ($unidade_id) {
                $_SESSION['unidade_id'] = $unidade_id;
            }

            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => $user['id'],
                    'nome' => $user['nome'],
                    'email' => $user['email'],
                    'perfil_id' => $user['perfil_id'],
                    'perfil' => $user['perfil_nome'],
                    'permissoes' => json_decode($user['permissoes'], true)
                ]
            ];
        } catch (Exception $e) {
            logError("Erro no login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    // Logout do usuário
    public function logout()
    {
        session_destroy();
        return ['success' => true, 'message' => 'Logout realizado com sucesso'];
    }

    // Verificar se usuário está autenticado
    public function isAuthenticated()
    {
        return checkAuth() !== false;
    }

    // Obter dados do usuário atual
    public function getCurrentUser()
    {
        $auth = checkAuth();
        if (!$auth) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'nome' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'perfil_id' => $_SESSION['perfil_id'],
            'perfil_nome' => $_SESSION['perfil_nome'],
            'permissoes' => $_SESSION['permissoes']
        ];
    }

    // Verificar permissão específica
    public function hasPermission($permission)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return checkPermission($permission, $user['permissoes']);
    }

    // Registrar novo usuário (apenas para responsáveis)
    public function registerResponsavel($data)
    {
        try {
            $this->db->beginTransaction();

            // Verificar se email já existe
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email já cadastrado'];
            }

            // Criar usuário
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (nome, email, senha, perfil_id) 
                VALUES (?, ?, ?, 6)
            ");
            $hashedPassword = password_hash($data['senha'], PASSWORD_DEFAULT);
            $stmt->execute([$data['nome'], $data['email'], $hashedPassword]);
            $userId = $this->db->lastInsertId();

            // Criar responsável
            $stmt = $this->db->prepare("
                INSERT INTO responsaveis (nome, cpf, telefone, email, endereco, tipo, usuario_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['nome'],
                $data['cpf'],
                $data['telefone'],
                $data['email'],
                $data['endereco'],
                $data['tipo'],
                $userId
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Cadastro realizado com sucesso',
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erro no registro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    // Alterar senha
    public function changePassword($currentPassword, $newPassword)
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não autenticado'];
            }

            // Verificar senha atual
            $stmt = $this->db->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();

            if (!password_verify($currentPassword, $userData['senha'])) {
                return ['success' => false, 'message' => 'Senha atual incorreta'];
            }

            // Atualizar senha
            $stmt = $this->db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt->execute([$hashedPassword, $user['id']]);

            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
        } catch (Exception $e) {
            logError("Erro ao alterar senha: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    // Obter perfil do usuário
    public function getUserProfile()
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não autenticado'];
            }

            $stmt = $this->db->prepare("
                SELECT u.*, p.nome as perfil_nome, p.descricao as perfil_descricao
                FROM usuarios u
                JOIN perfis_acesso p ON u.perfil_id = p.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user['id']]);
            $profile = $stmt->fetch();

            return [
                'success' => true,
                'profile' => $profile
            ];
        } catch (Exception $e) {
            logError("Erro ao obter perfil: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $auth = new Auth();

    switch ($action) {
        case 'login':
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                jsonResponse(['success' => false, 'message' => 'Email e senha são obrigatórios'], 400);
            }

            $result = $auth->login($email, $password);
            jsonResponse($result);
            break;

        case 'logout':
            $result = $auth->logout();
            jsonResponse($result);
            break;

        case 'register':
            $data = [
                'nome' => sanitizeInput($_POST['nome'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'senha' => $_POST['senha'] ?? '',
                'cpf' => sanitizeInput($_POST['cpf'] ?? ''),
                'telefone' => sanitizeInput($_POST['telefone'] ?? ''),
                'endereco' => sanitizeInput($_POST['endereco'] ?? ''),
                'tipo' => sanitizeInput($_POST['tipo'] ?? 'ambos')
            ];

            if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
                jsonResponse(['success' => false, 'message' => 'Campos obrigatórios não preenchidos'], 400);
            }

            if (!validateEmail($data['email'])) {
                jsonResponse(['success' => false, 'message' => 'Email inválido'], 400);
            }

            if (strlen($data['senha']) < PASSWORD_MIN_LENGTH) {
                jsonResponse(['success' => false, 'message' => 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'], 400);
            }

            $result = $auth->registerResponsavel($data);
            jsonResponse($result);
            break;

        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                jsonResponse(['success' => false, 'message' => 'Senhas são obrigatórias'], 400);
            }

            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                jsonResponse(['success' => false, 'message' => 'Nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'], 400);
            }

            $result = $auth->changePassword($currentPassword, $newPassword);
            jsonResponse($result);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Ação não encontrada'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $auth = new Auth();

    switch ($action) {
        case 'profile':
            $result = $auth->getUserProfile();
            jsonResponse($result);
            break;

        case 'check_auth':
            if (isset($_SESSION['user_id']) && isset($_SESSION['perfil_id'])) {
                $user = [
                    'id' => $_SESSION['user_id'],
                    'nome' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'perfil_id' => $_SESSION['perfil_id'],
                    'perfil_nome' => $_SESSION['perfil_nome']
                ];
                jsonResponse(['success' => true, 'user' => $user]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Usuário não autenticado']);
            }
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Ação não encontrada'], 404);
    }
}
