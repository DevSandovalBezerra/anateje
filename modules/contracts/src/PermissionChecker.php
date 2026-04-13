<?php

declare(strict_types=1);

namespace Anateje\Contracts;

interface PermissionChecker
{
    public function hasPermission(string $permissionCode): bool;
    public function requirePermission(string $permissionCode): void;
}
