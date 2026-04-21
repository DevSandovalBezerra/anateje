<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class AuthLoginTest extends TestCase
{
    private \PDO $db;

    public static function setUpBeforeClass(): void
    {
        require_once TEST_PROJECT_ROOT . '/config/database.php';
    }

    protected function setUp(): void
    {
        $this->db = new \PDO(
            'mysql:host=' . TEST_DB_HOST . ';dbname=' . TEST_DB_NAME . ';charset=utf8mb4',
            TEST_DB_USER,
            TEST_DB_PASS,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );

        // Criar tabelas necessárias
        $this->db->exec("CREATE TABLE IF NOT EXISTS perfis_acesso (
            id INT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            permissoes JSON
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(190) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            perfil_id INT NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            ultimo_login DATETIME,
            unidade_id BIGINT UNSIGNED,
            FOREIGN KEY (perfil_id) REFERENCES perfis_acesso(id)
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS auth_login_attempts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL,
            ip_address VARCHAR(64) NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_auth_login_attempts_email (email),
            KEY idx_auth_login_attempts_ip (ip_address),
            KEY idx_auth_login_attempts_attempted_at (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Limpar dados anteriores
        $this->db->exec("TRUNCATE TABLE usuarios");
        $this->db->exec("DELETE FROM perfis_acesso");
        $this->db->exec("TRUNCATE TABLE auth_login_attempts");

        // Criar perfil de admin
        $this->db->exec("INSERT INTO perfis_acesso (id, nome, permissoes) VALUES (1, 'Admin', '{\"admin\": true}')");

        // Criar usuário de teste
        $hashedPassword = password_hash('senha123', PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO usuarios (nome, email, senha, perfil_id, ativo) VALUES (?, ?, ?, 1, 1)");
        $stmt->execute(['Teste User', 'teste@example.com', $hashedPassword]);
    }

    protected function tearDown(): void
    {
        $this->db = null;
    }

    public function testLoginFailsWithMissingEmail(): void
    {
        require_once TEST_PROJECT_ROOT . '/api/auth/login.php';

        $auth = new \Auth();

        $result = $auth->login('', 'senha123');

        self::assertFalse($result['success']);
        self::assertStringContainsString('obrigatorios', $result['message'] ?? '', 'Deve avisar que email é obrigatório');
    }

    public function testLoginFailsWithMissingPassword(): void
    {
        require_once TEST_PROJECT_ROOT . '/api/auth/login.php';

        $auth = new \Auth();
        $result = $auth->login('teste@example.com', '');

        self::assertFalse($result['success']);
        self::assertStringContainsString('obrigatorios', $result['message'] ?? '');
    }

    public function testLoginSucceedsWithValidCredentials(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        require_once TEST_PROJECT_ROOT . '/api/auth/login.php';

        $auth = new \Auth();
        $result = $auth->login('teste@example.com', 'senha123');

        self::assertTrue($result['success'] ?? false, 'Login deve ser bem-sucedido com credenciais válidas');
        self::assertNotNull($result['user']['id'] ?? null, 'Resposta deve conter ID do usuário');
        self::assertSame('teste@example.com', $result['user']['email']);
    }

    public function testLoginFailsWithInvalidPassword(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        require_once TEST_PROJECT_ROOT . '/api/auth/login.php';

        $auth = new \Auth();
        $result = $auth->login('teste@example.com', 'senhaErrada');

        self::assertFalse($result['success'] ?? false, 'Login deve falhar com senha incorreta');
        self::assertStringContainsString('Email ou senha invalidos', $result['message'] ?? '');
    }

    public function testLoginFailsWithNonexistentEmail(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        require_once TEST_PROJECT_ROOT . '/api/auth/login.php';

        $auth = new \Auth();
        $result = $auth->login('naoexiste@example.com', 'senha123');

        self::assertFalse($result['success'] ?? false, 'Login deve falhar com email inexistente');
    }
}
