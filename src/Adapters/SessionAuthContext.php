<?php

declare(strict_types=1);

namespace Anateje\Adapters;

use Anateje\Contracts\AuthContextProvider;

final class SessionAuthContext implements AuthContextProvider
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public function getPerfilId(): ?int
    {
        return isset($_SESSION['perfil_id']) ? (int) $_SESSION['perfil_id'] : null;
    }

    public function getUnidadeId(): ?int
    {
        return isset($_SESSION['unidade_id']) ? (int) $_SESSION['unidade_id'] : null;
    }

    public function isAuthenticated(): bool
    {
        return $this->getUserId() !== null;
    }
}
