<?php

declare(strict_types=1);

namespace Anateje\Adapters;

use Anateje\Contracts\AuthContextProvider;
use Anateje\Contracts\DbConnection;
use Anateje\Contracts\PermissionChecker;
use RuntimeException;

final class LegacyPermissionChecker implements PermissionChecker
{
    public function __construct(
        private AuthContextProvider $auth,
        private DbConnection $db
    ) {
    }

    public function hasPermission(string $permissionCode): bool
    {
        if (!$this->auth->isAuthenticated()) {
            return false;
        }

        $perfilId = $this->auth->getPerfilId();
        if ($perfilId === 1) {
            return true;
        }

        require_once dirname(__DIR__, 2) . '/api/v1/_bootstrap.php';
        
        $authArray = [
            'perfil_id' => $perfilId
        ];
        
        return anateje_has_permission_code($this->db->getPdo(), $authArray, $permissionCode);
    }

    public function requirePermission(string $permissionCode): void
    {
        if (!$this->hasPermission($permissionCode)) {
            throw new RuntimeException("Acesso negado para a permissão: {$permissionCode}", 403);
        }
    }
}
