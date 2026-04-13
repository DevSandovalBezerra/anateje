<?php

declare(strict_types=1);

namespace Anateje\AuditModule;

use Anateje\Contracts\Route;
use Anateje\Contracts\RouteProvider;
use Anateje\AuditModule\Http\ListAuditLogsHandler;

final class Module implements RouteProvider
{
    public function routes(): array
    {
        return [
            new Route('GET', '/api/v2/audit', ListAuditLogsHandler::class),
        ];
    }
}
