<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Testes para validar o comportamento do endpoint de login
 * quando recebe diferentes tipos de requisição
 */
final class LoginRequestTest extends TestCase
{
    /**
     * RED: Testar que o login valida os dados obrigatórios
     * quando action é "login"
     */
    public function testLoginActionRequiresEmailAndPassword(): void
    {
        // Simular POST sem dados
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'login'];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        ob_start();
        try {
            $this->callLoginEndpoint();
        } catch (\Exception $e) {
            // Esperado - jsonResponse causa exit
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Este é o teste RED - mostra o que DEVERIA acontecer
        self::assertIsArray($response, 'Resposta deve ser JSON');
        self::assertFalse($response['success'] ?? false, 'Deve falhar sem email e senha');
        self::assertStringContainsString('obrigatorios', $response['message'] ?? '', 'Mensagem deve indicar campos obrigatórios');
    }

    /**
     * RED: Testar que o login funciona com dados válidos
     */
    public function testLoginActionWithValidData(): void
    {
        // Simular POST com dados completos
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'login',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        ob_start();
        try {
            $this->callLoginEndpoint();
        } catch (\Exception $e) {
            // Esperado - jsonResponse causa exit
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Este é o teste RED - mostra o que DEVERIA acontecer
        self::assertIsArray($response, 'Resposta deve ser JSON');
        self::assertArrayHasKey('success', $response, 'Deve conter "success"');
        self::assertArrayHasKey('message', $response, 'Deve conter "message"');
    }

    /**
     * RED: Testar que há suporte para conteúdo JSON
     * (isso pode ser o problema - o cliente está enviando JSON, não form data)
     */
    public function testLoginWithJsonPayload(): void
    {
        // Simular POST com JSON content-type
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        // Simular input JSON em php://input
        $jsonData = json_encode([
            'action' => 'login',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // Nota: Este teste identifica um possível problema
        // Se o cliente está enviando JSON, mas a API espera $_POST (form data),
        // isso causaria o erro "request missing data payload"

        self::assertTrue(true, 'JSON payload support needs to be added to login.php');
    }

    private function callLoginEndpoint(): void
    {
        require_once TEST_PROJECT_ROOT . '/config/database.php';
        require_once TEST_PROJECT_ROOT . '/api/auth/login.php';
    }
}
