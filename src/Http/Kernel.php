<?php

declare(strict_types=1);

namespace Anateje\Http;

use Anateje\Container\Container;
use Anateje\Contracts\ResponseFactory;
use Anateje\Contracts\Route;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;

use function FastRoute\simpleDispatcher;

final class Kernel
{
    private Dispatcher $dispatcher;

    public function __construct(
        array $routes,
        private Container $container
    ) {
        $this->dispatcher = simpleDispatcher(static function (RouteCollector $collector) use ($routes): void {
            foreach ($routes as $route) {
                if (!$route instanceof Route) {
                    throw new RuntimeException('Rota inválida: esperado ' . Route::class . '.');
                }
                $collector->addRoute($route->method(), $route->path(), $route->handlerClass());
            }
        });
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        $responseFactory = $this->container->get(ResponseFactory::class);
        if (!$responseFactory instanceof ResponseFactory) {
            throw new RuntimeException('ResponseFactory não configurado no container.');
        }

        try {
            $info = $this->dispatcher->dispatch($method, $path);

            if ($info[0] === Dispatcher::NOT_FOUND) {
                return $responseFactory->json(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Rota não encontrada']], 404);
            }

            if ($info[0] === Dispatcher::METHOD_NOT_ALLOWED) {
                return $responseFactory->json(['ok' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido']], 405);
            }

            if ($info[0] !== Dispatcher::FOUND) {
                return $responseFactory->json(['ok' => false, 'error' => ['code' => 'DISPATCH_ERROR', 'message' => 'Falha no roteamento']], 500);
            }

            $handlerClass = (string) $info[1];
            $vars = is_array($info[2] ?? null) ? $info[2] : [];
            foreach ($vars as $k => $v) {
                $request = $request->withAttribute((string) $k, $v);
            }

            $handler = $this->container->get($handlerClass);
            if (!$handler instanceof RequestHandlerInterface) {
                throw new RuntimeException("Handler inválido: {$handlerClass} não implementa RequestHandlerInterface.");
            }

            return $handler->handle($request);
        } catch (Throwable $e) {
            $status = (int) $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            $message = $status === 500 ? 'Erro interno do servidor' : $e->getMessage();
            $code = $status === 403 ? 'FORBIDDEN' : ($status === 401 ? 'UNAUTH' : ($status === 422 ? 'VALIDATION' : 'FAIL'));

            return $responseFactory->json(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], $status);
        }
    }
}
