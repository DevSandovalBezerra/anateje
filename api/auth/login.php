<?php
// ANATEJE - API de autenticacao (login, sessao e recuperacao de senha)

require_once __DIR__ . '/../../config/database.php';

const AUTH_MAX_LOGIN_ATTEMPTS = 5;
const AUTH_LOGIN_WINDOW_MINUTES = 15;
const AUTH_LOGIN_COOLDOWN_MINUTES = 15;
const AUTH_RESET_TTL_SECONDS = 3600;
const AUTH_MAX_RESET_REQUESTS = 3;
const AUTH_RESET_WINDOW_MINUTES = 30;

function auth_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return trim((string) $ip) !== '' ? trim((string) $ip) : '0.0.0.0';
}

function auth_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function auth_ensure_security_tables(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS auth_login_attempts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        ip_address VARCHAR(64) NOT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_auth_login_attempts_email (email),
        KEY idx_auth_login_attempts_ip (ip_address),
        KEY idx_auth_login_attempts_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL,
        requested_ip VARCHAR(64) NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_password_reset_tokens_hash (token_hash),
        KEY idx_password_reset_tokens_user (user_id),
        KEY idx_password_reset_tokens_expires (expires_at),
        KEY idx_password_reset_tokens_used (used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function auth_log_login_attempt(PDO $db, string $email, string $ip, bool $success): void
{
    $st = $db->prepare('INSERT INTO auth_login_attempts (email, ip_address, success) VALUES (?, ?, ?)');
    $st->execute([$email, $ip, $success ? 1 : 0]);
}

function auth_clear_failed_attempts(PDO $db, string $email, string $ip): void
{
    $st = $db->prepare('DELETE FROM auth_login_attempts WHERE success = 0 AND (email = ? OR ip_address = ?)');
    $st->execute([$email, $ip]);
}

function auth_login_rate_limit_status(PDO $db, string $email, string $ip): array
{
    $lookback = max(AUTH_LOGIN_WINDOW_MINUTES, AUTH_LOGIN_COOLDOWN_MINUTES);

    $sql = "SELECT COUNT(*) AS total, MAX(attempted_at) AS last_try
        FROM auth_login_attempts
        WHERE success = 0
          AND attempted_at >= (NOW() - INTERVAL {$lookback} MINUTE)
          AND (email = ? OR ip_address = ?)";

    $st = $db->prepare($sql);
    $st->execute([$email, $ip]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'last_try' => null];

    $total = (int) ($row['total'] ?? 0);
    if ($total < AUTH_MAX_LOGIN_ATTEMPTS || empty($row['last_try'])) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $lastTs = strtotime((string) $row['last_try']);
    if ($lastTs === false) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $retryAfter = ($lastTs + (AUTH_LOGIN_COOLDOWN_MINUTES * 60)) - time();
    if ($retryAfter > 0) {
        return ['blocked' => true, 'retry_after' => $retryAfter];
    }

    return ['blocked' => false, 'retry_after' => 0];
}

function auth_reset_rate_limit_status(PDO $db, int $userId, string $ip): array
{
    $sql = "SELECT COUNT(*) AS total, MAX(created_at) AS last_try
        FROM password_reset_tokens
        WHERE created_at >= (NOW() - INTERVAL " . AUTH_RESET_WINDOW_MINUTES . " MINUTE)
          AND (user_id = ? OR requested_ip = ?)";

    $st = $db->prepare($sql);
    $st->execute([$userId, $ip]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'last_try' => null];

    $total = (int) ($row['total'] ?? 0);
    if ($total < AUTH_MAX_RESET_REQUESTS || empty($row['last_try'])) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $lastTs = strtotime((string) $row['last_try']);
    if ($lastTs === false) {
        return ['blocked' => false, 'retry_after' => 0];
    }

    $retryAfter = ($lastTs + (AUTH_RESET_WINDOW_MINUTES * 60)) - time();
    if ($retryAfter > 0) {
        return ['blocked' => true, 'retry_after' => $retryAfter];
    }

    return ['blocked' => false, 'retry_after' => 0];
}

function auth_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $apiPos = strpos($scriptName, '/api/');
    if ($apiPos === false) {
        return $scheme . '://' . $host;
    }
    $basePath = substr($scriptName, 0, $apiPos);
    return $scheme . '://' . $host . $basePath;
}

class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
        auth_ensure_security_tables($this->db);
    }

    public function login($email, $password)
    {
        $email = auth_normalize_email((string) $email);
        $ip = auth_client_ip();

        try {
            $limit = auth_login_rate_limit_status($this->db, $email, $ip);
            if (!empty($limit['blocked'])) {
                return [
                    'success' => false,
                    'code' => 'RATE_LIMIT',
                    'retry_after' => (int) ($limit['retry_after'] ?? 0),
                    'message' => 'Muitas tentativas de login. Tente novamente em alguns minutos.'
                ];
            }

            $stmt = $this->db->prepare("
                SELECT u.id, u.nome, u.email, u.senha, u.perfil_id, p.nome AS perfil_nome, p.permissoes
                FROM usuarios u
                JOIN perfis_acesso p ON u.perfil_id = p.id
                WHERE u.email = ? AND u.ativo = 1
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify((string) $password, (string) $user['senha'])) {
                auth_log_login_attempt($this->db, $email, $ip, false);
                return ['success' => false, 'message' => 'Email ou senha invalidos'];
            }

            $this->db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([(int) $user['id']]);

            $unidadeId = null;
            if ((int) $user['perfil_id'] !== 1) {
                try {
                    $stUnidade = $this->db->prepare('SELECT unidade_id FROM usuarios WHERE id = ? AND ativo = 1');
                    $stUnidade->execute([(int) $user['id']]);
                    $usuario = $stUnidade->fetch(PDO::FETCH_ASSOC);
                    if ($usuario && !empty($usuario['unidade_id'])) {
                        $unidadeId = $usuario['unidade_id'];
                    }
                } catch (Exception $e) {
                    logDebug('Aviso unidade_id usuario ' . (int) $user['id'] . ': ' . $e->getMessage());
                }
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }
            $_SESSION = [];

            $token = generateToken((int) $user['id'], (int) $user['perfil_id']);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['perfil_id'] = (int) $user['perfil_id'];
            $_SESSION['perfil_nome'] = $user['perfil_nome'];
            $_SESSION['permissoes'] = json_decode((string) $user['permissoes'], true);
            $_SESSION['token'] = $token;
            if ($unidadeId) {
                $_SESSION['unidade_id'] = $unidadeId;
            }
            initializeAuthSessionSecurity();

            auth_log_login_attempt($this->db, $email, $ip, true);
            auth_clear_failed_attempts($this->db, $email, $ip);

            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => (int) $user['id'],
                    'nome' => $user['nome'],
                    'email' => $user['email'],
                    'perfil_id' => (int) $user['perfil_id'],
                    'perfil' => $user['perfil_nome'],
                    'permissoes' => json_decode((string) $user['permissoes'], true)
                ]
            ];
        } catch (Exception $e) {
            logError('Erro no login: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    public function logout()
    {
        destroyAuthSession();
        return ['success' => true, 'message' => 'Logout realizado com sucesso'];
    }

    public function isAuthenticated()
    {
        return checkAuth() !== false;
    }

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

    public function hasPermission($permission)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        return checkPermission($permission, $user['permissoes']);
    }

    public function registerResponsavel($data)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email ja cadastrado'];
            }

            $stmt = $this->db->prepare('INSERT INTO usuarios (nome, email, senha, perfil_id) VALUES (?, ?, ?, 6)');
            $hashedPassword = password_hash((string) $data['senha'], PASSWORD_DEFAULT);
            $stmt->execute([$data['nome'], $data['email'], $hashedPassword]);
            $userId = $this->db->lastInsertId();

            $stmt = $this->db->prepare('INSERT INTO responsaveis (nome, cpf, telefone, email, endereco, tipo, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
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
            return ['success' => true, 'message' => 'Cadastro realizado com sucesso', 'user_id' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError('Erro no registro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    public function changePassword($currentPassword, $newPassword)
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                return ['success' => false, 'message' => 'Usuario nao autenticado'];
            }

            $stmt = $this->db->prepare('SELECT senha FROM usuarios WHERE id = ?');
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData || !password_verify((string) $currentPassword, (string) $userData['senha'])) {
                return ['success' => false, 'message' => 'Senha atual incorreta'];
            }

            $stmt = $this->db->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
            $hashedPassword = password_hash((string) $newPassword, PASSWORD_DEFAULT);
            $stmt->execute([$hashedPassword, $user['id']]);

            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
        } catch (Exception $e) {
            logError('Erro ao alterar senha: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    public function getUserProfile()
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                return ['success' => false, 'message' => 'Usuario nao autenticado'];
            }

            $stmt = $this->db->prepare("
                SELECT u.*, p.nome AS perfil_nome, p.descricao AS perfil_descricao
                FROM usuarios u
                JOIN perfis_acesso p ON u.perfil_id = p.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user['id']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            return ['success' => true, 'profile' => $profile];
        } catch (Exception $e) {
            logError('Erro ao obter perfil: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    public function requestPasswordReset($email)
    {
        try {
            $email = auth_normalize_email((string) $email);
            if ($email === '' || !validateEmail($email)) {
                return ['success' => true, 'message' => 'Se o email existir, enviaremos as instrucoes de recuperacao.'];
            }

            $st = $this->db->prepare('SELECT id, email FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1');
            $st->execute([$email]);
            $user = $st->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                return ['success' => true, 'message' => 'Se o email existir, enviaremos as instrucoes de recuperacao.'];
            }

            $limit = auth_reset_rate_limit_status($this->db, (int) $user['id'], auth_client_ip());
            if (!empty($limit['blocked'])) {
                logError('Rate limit de recuperacao de senha acionado', [
                    'user_id' => (int) $user['id'],
                    'ip' => auth_client_ip(),
                    'retry_after' => (int) ($limit['retry_after'] ?? 0)
                ]);
                return ['success' => true, 'message' => 'Se o email existir, enviaremos as instrucoes de recuperacao.'];
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + AUTH_RESET_TTL_SECONDS);
            $ip = auth_client_ip();

            $this->db->beginTransaction();
            $this->db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL')->execute([(int) $user['id']]);
            $ins = $this->db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, requested_ip, expires_at) VALUES (?, ?, ?, ?)');
            $ins->execute([(int) $user['id'], $tokenHash, $ip, $expiresAt]);
            $this->db->commit();

            $response = ['success' => true, 'message' => 'Se o email existir, enviaremos as instrucoes de recuperacao.'];
            if (isDevelopment()) {
                $base = auth_base_url();
                $response['debug_reset_url'] = $base . '/frontend/auth/reset-password.html?token=' . urlencode($token);
            }
            return $response;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao solicitar recuperacao de senha: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    public function validateResetToken($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return ['success' => false, 'message' => 'Token ausente'];
        }

        $hash = hash('sha256', $token);
        $st = $this->db->prepare('SELECT id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW() LIMIT 1');
        $st->execute([$hash]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return ['success' => (bool) $row, 'valid' => (bool) $row];
    }

    public function resetPasswordByToken($token, $newPassword)
    {
        try {
            $token = trim((string) $token);
            if ($token === '') {
                return ['success' => false, 'message' => 'Token ausente'];
            }
            if (strlen((string) $newPassword) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'];
            }

            $hash = hash('sha256', $token);
            $this->db->beginTransaction();

            $st = $this->db->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW() LIMIT 1 FOR UPDATE');
            $st->execute([$hash]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                logError('Tentativa de reset com token invalido/expirado', ['ip' => auth_client_ip()]);
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Token invalido ou expirado'];
            }

            $newHash = password_hash((string) $newPassword, PASSWORD_DEFAULT);
            $this->db->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')->execute([$newHash, (int) $row['user_id']]);
            $this->db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Senha redefinida com sucesso'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Erro ao redefinir senha: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $auth = new Auth();

    switch ($action) {
        case 'login':
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if (empty($email) || empty($password)) {
                jsonResponse(['success' => false, 'message' => 'Email e senha sao obrigatorios'], 400);
            }
            $result = $auth->login($email, $password);
            if (empty($result['success']) && ($result['code'] ?? '') === 'RATE_LIMIT') {
                jsonResponse($result, 429);
            }
            if (empty($result['success'])) {
                // Manter HTTP 200 para credencial invalida evita ruido de "Failed to load resource"
                // no frontend, preservando o contrato funcional via success=false.
                jsonResponse($result, 200);
            }
            jsonResponse($result);
            break;

        case 'logout':
            jsonResponse($auth->logout());
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
                jsonResponse(['success' => false, 'message' => 'Campos obrigatorios nao preenchidos'], 400);
            }
            if (!validateEmail($data['email'])) {
                jsonResponse(['success' => false, 'message' => 'Email invalido'], 400);
            }
            if (strlen((string) $data['senha']) < PASSWORD_MIN_LENGTH) {
                jsonResponse(['success' => false, 'message' => 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'], 400);
            }

            $result = $auth->registerResponsavel($data);
            jsonResponse($result, !empty($result['success']) ? 200 : 400);
            break;

        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            if (empty($currentPassword) || empty($newPassword)) {
                jsonResponse(['success' => false, 'message' => 'Senhas sao obrigatorias'], 400);
            }
            if (strlen((string) $newPassword) < PASSWORD_MIN_LENGTH) {
                jsonResponse(['success' => false, 'message' => 'Nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'], 400);
            }

            $result = $auth->changePassword($currentPassword, $newPassword);
            jsonResponse($result, !empty($result['success']) ? 200 : 400);
            break;

        case 'request_password_reset':
            $email = sanitizeInput($_POST['email'] ?? '');
            $result = $auth->requestPasswordReset($email);
            jsonResponse($result, !empty($result['success']) ? 200 : 400);
            break;

        case 'reset_password':
            $token = $_POST['token'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $result = $auth->resetPasswordByToken($token, $newPassword);
            jsonResponse($result, !empty($result['success']) ? 200 : 400);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Acao nao encontrada'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $auth = new Auth();

    switch ($action) {
        case 'profile':
            $result = $auth->getUserProfile();
            jsonResponse($result, !empty($result['success']) ? 200 : 401);
            break;

        case 'check_auth':
            $tokenData = checkAuth();
            if ($tokenData && isset($_SESSION['user_id']) && isset($_SESSION['perfil_id'])) {
                $user = [
                    'id' => $_SESSION['user_id'],
                    'nome' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'perfil_id' => $_SESSION['perfil_id'],
                    'perfil_nome' => $_SESSION['perfil_nome']
                ];
                jsonResponse([
                    'success' => true,
                    'user' => $user,
                    'session' => [
                        'expires_at' => date('c', (int) ($tokenData['exp'] ?? time())),
                        'expires_in' => max(0, (int) ($tokenData['exp'] ?? time()) - time()),
                        'idle_timeout' => SESSION_IDLE_TIMEOUT
                    ]
                ], 200);
            }
            jsonResponse(['success' => false, 'message' => 'Usuario nao autenticado'], 401);
            break;

        case 'validate_reset_token':
            $token = $_GET['token'] ?? '';
            $result = $auth->validateResetToken($token);
            jsonResponse($result, !empty($result['success']) ? 200 : 400);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Acao nao encontrada'], 404);
    }
}

jsonResponse(['success' => false, 'message' => 'Metodo nao permitido'], 405);
