<?php

declare(strict_types=1);

namespace Anateje\Adapters;

use Anateje\Contracts\CsrfValidator;
use RuntimeException;

final class SessionCsrfValidator implements CsrfValidator
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function generateToken(): string
    {
        $token = (string) ($_SESSION['csrf_token'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (\Throwable $e) {
                $token = hash('sha256', uniqid('csrf', true) . '|' . mt_rand());
            }
            $_SESSION['csrf_token'] = $token;
        }
        return $token;
    }

    public function validateToken(string $token): bool
    {
        $expected = $this->generateToken();
        return hash_equals($expected, $token);
    }

    public function requireValidToken(): void
    {
        $provided = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if ($provided === '') {
            $provided = trim((string) ($_POST['csrf_token'] ?? ''));
        }

        if (!$this->validateToken($provided)) {
            throw new RuntimeException("Token CSRF invalido ou ausente", 403);
        }
    }
}
