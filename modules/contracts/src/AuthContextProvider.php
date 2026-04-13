<?php

declare(strict_types=1);

namespace Anateje\Contracts;

interface AuthContextProvider
{
    public function getUserId(): ?int;
    public function getPerfilId(): ?int;
    public function getUnidadeId(): ?int;
    public function isAuthenticated(): bool;
}
