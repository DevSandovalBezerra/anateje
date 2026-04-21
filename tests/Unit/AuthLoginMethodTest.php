<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AuthLoginMethodTest extends TestCase
{
    private static \Auth $auth;

    public static function setUpBeforeClass(): void
    {
        require_once TEST_PROJECT_ROOT . '/config/database.php';

        // Previne que o arquivo auth/login.php execute código GET/POST/HTTP
        $_SERVER['REQUEST_METHOD'] = 'NONE';

        ob_start();
        @require_once TEST_PROJECT_ROOT . '/api/auth/login.php';
        ob_end_clean();

        self::$auth = new \Auth();
    }

    public function testLoginReturnsArrayWithSuccessKey(): void
    {
        $result = self::$auth->login('nonexistent@example.com', 'password123');

        self::assertIsArray($result, 'login() deve retornar um array');
        self::assertArrayHasKey('success', $result, 'Resposta deve conter chave "success"');
        self::assertIsBool($result['success'], 'Chave "success" deve ser booleana');
    }

    public function testLoginFailsWithNonexistentEmail(): void
    {
        $result = self::$auth->login('nao_existe@example.com', 'qualquer_senha');

        self::assertFalse($result['success'] ?? false, 'Login deve falhar com email inexistente');
        self::assertNotNull($result['message'] ?? null, 'Resposta deve conter mensagem de erro');
        self::assertStringContainsString('Email ou senha', $result['message'] ?? '', 'Mensagem deve indicar credencial inválida');
    }

    public function testLoginReturnsCorrectStructure(): void
    {
        $result = self::$auth->login('test@example.com', 'password');

        // Verificar estrutura básica da resposta
        self::assertIsArray($result);
        self::assertArrayHasKey('success', $result);
        self::assertArrayHasKey('message', $result);

        // Em caso de sucesso, deve ter user
        if ($result['success']) {
            self::assertArrayHasKey('user', $result);
            self::assertIsArray($result['user']);
        }
    }
}
