<?php

declare(strict_types=1);

namespace Anateje\EventsModule\Http\Handlers;

use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

abstract class BaseHandler
{
    protected function __construct(protected Psr17Factory $factory) {}

    protected function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->factory->createResponse($status)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(array_merge(['ok' => $status >= 200 && $status < 300], $data)));
        return $response;
    }

    protected function error(string $code, string $message, int $status = 400): ResponseInterface
    {
        return $this->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
