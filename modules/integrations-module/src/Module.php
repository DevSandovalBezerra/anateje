<?php

declare(strict_types=1);

namespace Anateje\IntegrationsModule;

use Anateje\Contracts\Route;
use Anateje\Contracts\RouteProvider;
use Anateje\IntegrationsModule\Http\ListIntegrationsHandler;
use Anateje\IntegrationsModule\Http\SaveIntegrationHandler;
use Anateje\IntegrationsModule\Http\TestIntegrationHandler;

final class Module implements RouteProvider
{
    public function routes(): array
    {
        return [
            new Route('GET', '/api/v2/integrations', ListIntegrationsHandler::class),
            new Route('POST', '/api/v2/integrations', SaveIntegrationHandler::class),
            new Route('POST', '/api/v2/integrations/test', TestIntegrationHandler::class),
        ];
    }
}
