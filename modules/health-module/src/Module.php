<?php

declare(strict_types=1);

namespace Anateje\HealthModule;

use Anateje\Contracts\Route;
use Anateje\Contracts\RouteProvider;
use Anateje\HealthModule\Http\HealthHandler;

final class Module implements RouteProvider
{
    public function routes(): array
    {
        return [
            new Route('GET', '/api/v2/health', HealthHandler::class),
        ];
    }
}

