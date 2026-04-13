<?php

declare(strict_types=1);

namespace Anateje\HealthModule\Http;

use Anateje\Contracts\ResponseFactory;
use Anateje\HealthModule\Application\Health\GetHealthStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HealthHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetHealthStatus $useCase,
        private ResponseFactory $responses
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responses->json(['ok' => true, 'data' => $this->useCase->execute()]);
    }
}

