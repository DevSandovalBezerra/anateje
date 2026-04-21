<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once TEST_PROJECT_ROOT . '/config/database.php';
        ob_start();
        require_once TEST_PROJECT_ROOT . '/api/auth/login.php';
        ob_end_clean();
    }

    public function testLoginClassExists(): void
    {
        // RED: Verificar que a classe Auth existe
        self::assertTrue(class_exists('Auth'), 'Classe Auth deve existir');
    }

    public function testLoginReturnsArrayWithSuccessKey(): void
    {
        // RED: O login() deve retornar um array com chave 'success'
        $auth = new \Auth();
        $result = $auth->login('nonexistent@example.com', 'password123');

        self::assertIsArray($result, 'login() deve retornar um array');
        self::assertArrayHasKey('success', $result, 'Resposta deve conter chave "success"');
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        // RED: O login deve retornar success=false com credenciais inválidas
        $auth = new \Auth();
        $result = $auth->login('nonexistent@example.com', 'wrongpassword');

        self::assertFalse($result['success'] ?? false, 'Login deve falhar com credenciais inexistentes');
        self::assertArrayHasKey('message', $result, 'Resposta deve conter mensagem de erro');
    }

    public function testAuthClassHasLoginMethod(): void
    {
        // RED: Verificar que Auth tem método login
        $reflection = new \ReflectionClass('Auth');
        self::assertTrue($reflection->hasMethod('login'), 'Auth deve ter método login');

        $method = $reflection->getMethod('login');
        self::assertTrue($method->isPublic(), 'Método login deve ser público');
    }
}
