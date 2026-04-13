<?php

declare(strict_types=1);

use Anateje\Container\Container;
use Anateje\Http\Kernel;
use Anateje\Http\NyholmResponseFactory;
use Anateje\Contracts\RouteProvider;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new Container();
$container->set(\Anateje\Contracts\ResponseFactory::class, static fn (Container $c) => new NyholmResponseFactory());

$container->set(\Anateje\Contracts\DbConnection::class, static fn (Container $c) => new \Anateje\Adapters\LegacyDbConnection());
$container->set(\Anateje\Contracts\AuthContextProvider::class, static fn (Container $c) => new \Anateje\Adapters\SessionAuthContext());
$container->set(\Anateje\Contracts\PermissionChecker::class, static fn (Container $c) => new \Anateje\Adapters\LegacyPermissionChecker(
    $c->get(\Anateje\Contracts\AuthContextProvider::class),
    $c->get(\Anateje\Contracts\DbConnection::class)
));
$container->set(\Anateje\Contracts\AuditLogger::class, static fn (Container $c) => new \Anateje\Adapters\LegacyAuditLogger(
    $c->get(\Anateje\Contracts\DbConnection::class),
    $c->get(\Anateje\Contracts\AuthContextProvider::class)
));
$container->set(\Anateje\Contracts\CsrfValidator::class, static fn (Container $c) => new \Anateje\Adapters\SessionCsrfValidator());
$container->set(\Anateje\Contracts\HttpClient::class, static fn (Container $c) => new \Anateje\Adapters\LegacyHttpClient());

$providers = require dirname(__DIR__) . '/config/modules.php';

$routes = [];
foreach ($providers as $providerClass) {
    $provider = $container->get($providerClass);
    if (!$provider instanceof RouteProvider) {
        throw new RuntimeException("Módulo inválido: {$providerClass} não implementa RouteProvider.");
    }
    foreach ($provider->routes() as $route) {
        $routes[] = $route;
    }
}

return new Kernel($routes, $container);
