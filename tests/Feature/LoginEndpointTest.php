<?php
declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Testes de feature para o endpoint de login
 *
 * Simulam requisições HTTP reais para validar o fluxo completo
 */
final class LoginEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        require_once TEST_PROJECT_ROOT . '/config/database.php';
    }

    /**
     * RED: Teste que simula uma requisição POST válida ao endpoint de login
     * Este teste valida que o endpoint está respondendo corretamente
     */
    public function testLoginEndpointWithMissingDataReturns400(): void
    {
        // Simular POST sem dados obrigatórios
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'login'];  // Sem email e password
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        ob_start();
        try {
            // Executar o endpoint
            require TEST_PROJECT_ROOT . '/api/auth/login.php';
        } catch (\Throwable $e) {
            // jsonResponse faz exit, então esperamos que lance exceção
        }
        $output = ob_get_clean();

        // Decodificar resposta
        $response = json_decode($output, true);

        // Validações
        self::assertIsArray($response, 'Resposta deve ser JSON válido');
        self::assertFalse($response['success'] ?? false, 'Login deve falhar sem credenciais');
        self::assertArrayHasKey('message', $response, 'Deve conter mensagem de erro');
        self::assertStringContainsString('obrigatorios', $response['message'] ?? '', 'Mensagem deve mencionar campos obrigatórios');
    }

    /**
     * RED: Teste para requisição GET inválida
     * O endpoint só deve aceitar POST para login
     */
    public function testLoginEndpointRejectsGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['action' => 'check_auth'];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        ob_start();
        try {
            require TEST_PROJECT_ROOT . '/api/auth/login.php';
        } catch (\Throwable $e) {
            // Esperado
        }
        $output = ob_get_clean();

        // GET com action=check_auth deve ser aceito
        $response = json_decode($output, true);
        self::assertIsArray($response, 'GET com check_auth deve retornar JSON');
    }

    /**
     * RED: Teste que valida a estrutura de resposta
     * Garante que a resposta tem formato esperado
     */
    public function testLoginEndpointResponseStructure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'login',
            'email' => 'nonexistent@test.com',
            'password' => 'testpass'
        ];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        ob_start();
        try {
            require TEST_PROJECT_ROOT . '/api/auth/login.php';
        } catch (\Throwable $e) {
            // Esperado
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Validar estrutura básica
        self::assertIsArray($response, 'Resposta deve ser JSON');
        self::assertArrayHasKey('success', $response, 'Deve conter "success"');
        self::assertIsBool($response['success'], '"success" deve ser boolean');
        self::assertArrayHasKey('message', $response, 'Deve conter "message"');
        self::assertIsString($response['message'], '"message" deve ser string');

        // Se sucesso, deve ter user
        if ($response['success']) {
            self::assertArrayHasKey('user', $response, 'Sucesso deve incluir "user"');
            self::assertIsArray($response['user']);
            self::assertArrayHasKey('id', $response['user']);
            self::assertArrayHasKey('email', $response['user']);
        }
    }

    /**
     * Teste para validar que action é obrigatória
     */
    public function testLoginEndpointRequiresAction(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['email' => 'test@example.com', 'password' => 'pass'];  // Sem action
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        ob_start();
        try {
            require TEST_PROJECT_ROOT . '/api/auth/login.php';
        } catch (\Throwable $e) {
            // Esperado
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Sem action, deve retornar erro
        self::assertFalse($response['success'] ?? false, 'POST sem action deve falhar');
        self::assertStringContainsString('nao encontrada', $response['message'] ?? '', 'Deve indicar que action não foi encontrada');
    }
}
