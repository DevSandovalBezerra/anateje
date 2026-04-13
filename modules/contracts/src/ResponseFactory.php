<?php

declare(strict_types=1);

namespace Anateje\Contracts;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactory
{
    public function json(array $data, int $statusCode = 200, array $headers = []): ResponseInterface;
}

