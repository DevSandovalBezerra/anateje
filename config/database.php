<?php
// LiderGest - Configuração do Banco de Dados
// Sistema de Gestão Pedagógico-Financeira Líder School
if (!defined('BASE_PATH')) {
    // Configurações gerais
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Não exibe erros na tela
    ini_set('log_errors', 1); // Ativa o log de erros

    // Definir o diretório raiz do projeto
    define('BASE_PATH', dirname(__DIR__));

    $log_file_path = BASE_PATH . '/logs/php_errors.log';
    @ini_set('error_log', $log_file_path);
}

require_once __DIR__ . '/paths.php';

class Database
{
    // Detectar ambiente automaticamente
    private function getConfig()
    {
        // Verificar se está em produção (hospedagem)
        $isProduction = (
            strpos($_SERVER['HTTP_HOST'] ?? '', 'sbsystems.com.br') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', 'lidergest') !== false ||
            strpos($_SERVER['SERVER_NAME'] ?? '', 'sbsystems.com.br') !== false ||
            (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1')
        );
        
        // Verificar variáveis de ambiente (prioridade)
        if (getenv('DB_HOST')) {
            return [
                'host' => getenv('DB_HOST'),
                'db_name' => getenv('DB_NAME'),
                'username' => getenv('DB_USER'),
                'password' => getenv('DB_PASS')
            ];
        }
        
        if ($isProduction) {
            // CONFIGURAÇÃO DE PRODUÇÃO (HOSPEDAGEM)
            return [
                'host' => 'localhost',
                'db_name' => 'brunor90_lidergest',
                'username' => 'brunor90_root',
                'password' => 'k0gn022'
            ];
        } else {
            // CONFIGURAÇÃO DE DESENVOLVIMENTO (LOCAL)
            return [
                'host' => 'localhost',
                'db_name' => 'brunor90_lidergest',
                'username' => 'root',
                'password' => ''
            ];
        }
    }
    
    private $host;
    private $db_name;
    private $username;
    private $password;


    private $conn;


    public function __construct()
    {
        $config = $this->getConfig();
        $this->host = $config['host'];
        $this->db_name = $config['db_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
    
    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $exception) {
            logError("Erro de conexão: " . $exception->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }

        return $this->conn;
    }
}

// Configurações gerais do sistema
define('SITE_NAME', 'LiderGest');
define('SITE_URL', 'http://localhost/anateje');
define('SITE_VERSION', '1.0.0');

// Configurações de segurança
define('JWT_SECRET', 'lidergest_secret_key_2024');
define('SESSION_TIMEOUT', 3600); // 1 hora
define('PASSWORD_MIN_LENGTH', 6);

// Configurações de upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Configurações de email (para futuras implementações)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Configurações de pagamento (para futuras implementações)
define('PIX_KEY', '');
define('BOLETO_API_URL', '');

// Configurações de gamificação
define('LIDERCOIN_INITIAL_BALANCE', 0);
define('LIDERCOIN_PRESENCE_REWARD', 10);
define('LIDERCOIN_TRILHA_REWARD', 50);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Headers de segurança (apenas se não for uma requisição de API e se não houver output anterior)
if ((!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') === false) && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// Função para conectar ao banco
function getDB()
{
    $database = new Database();
    return $database->getConnection();
}

// Função para detectar ambiente (dev ou produção)
function isDevelopment()
{
    // Verificar variável de ambiente
    if (defined('LIDERGEST_ENV')) {
        return LIDERGEST_ENV === 'development';
    }
    
    // Detectar por hostname (localhost = dev)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (empty($host)) {
        $host = $_SERVER['SERVER_NAME'] ?? '';
    }
    
    return strpos($host, 'localhost') !== false || 
           strpos($host, '127.0.0.1') !== false ||
           strpos($host, '.local') !== false ||
           strpos($host, '.test') !== false;
}

// Função para log de erros (sempre loga)
function logError($message, $context = [])
{
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}

// Função para log de debug (apenas em desenvolvimento)
function logDebug($message, $context = [])
{
    if (!isDevelopment()) {
        return; // Não logar em produção
    }
    
    $logMessage = date('Y-m-d H:i:s') . " [DEBUG] - " . $message;
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}

// Função para resposta JSON (apenas se não existir)
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status = 200)
    {
        // Limpar qualquer output anterior se buffer estiver ativo
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (ob_get_level() > 0) {
            @ob_end_flush();
        }
        exit;
    }
}

// Função para sanitizar input
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Função para validar email
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para gerar token JWT simples
function generateToken($userId, $perfilId)
{
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'perfil_id' => $perfilId,
        'iat' => time(),
        'exp' => time() + SESSION_TIMEOUT
    ]);

    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

// Função para verificar token JWT
function verifyToken($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]));

    $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET, true);

    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }

    $payloadData = json_decode($payload, true);

    if ($payloadData['exp'] < time()) {
        return false;
    }

    return $payloadData;
}

// Função para verificar autenticação
function checkAuth()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
        return false;
    }

    $tokenData = verifyToken($_SESSION['token']);
    if (!$tokenData || $tokenData['user_id'] != $_SESSION['user_id']) {
        return false;
    }

    return $tokenData;
}

// Função para formatar moeda
function formatCurrency($value)
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Função para formatar data
function formatDate($date, $format = 'd/m/Y')
{
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

// Função para gerar número de contrato
function generateContractNumber()
{
    return 'CONTR-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Função para gerar código de cobrança
function generatePaymentCode($type = 'PAG')
{
    $prefix = strtoupper(substr($type, 0, 3));
    return $prefix . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Inicializar sessão (apenas se headers não foram enviados)
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Criar diretório de uploads se não existir
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
