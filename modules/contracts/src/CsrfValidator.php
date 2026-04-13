<?php

declare(strict_types=1);

namespace Anateje\Contracts;

interface CsrfValidator
{
    public function validateToken(string $token): bool;
    public function requireValidToken(): void;
    public function generateToken(): string;
}
