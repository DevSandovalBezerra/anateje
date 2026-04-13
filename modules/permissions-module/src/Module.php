<?php

declare(strict_types=1);

namespace Anateje\PermissionsModule;

use Anateje\Contracts\Route;
use Anateje\Contracts\RouteProvider;
use Anateje\PermissionsModule\Http\ListPermissionsHandler;
use Anateje\PermissionsModule\Http\SaveProfileHandler;
use Anateje\PermissionsModule\Http\DeleteProfileHandler;
use Anateje\PermissionsModule\Http\SaveProfilePermissionsHandler;

final class Module implements RouteProvider
{
    public function routes(): array
    {
        return [
            new Route('GET', '/api/v2/permissions', ListPermissionsHandler::class),
            new Route('POST', '/api/v2/permissions/profile', SaveProfileHandler::class),
            new Route('POST', '/api/v2/permissions/profile/delete', DeleteProfileHandler::class),
            new Route('POST', '/api/v2/permissions/profile/permissions', SaveProfilePermissionsHandler::class),
        ];
    }
}
