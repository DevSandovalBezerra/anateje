<?php

declare(strict_types=1);

namespace Anateje\Contracts;

final class Route
{
    private string $method;
    private string $path;
    private string $handlerClass;

    public function __construct(string $method, string $path, string $handlerClass)
    {
        $this->method = strtoupper(trim($method));
        $this->path = '/' . ltrim(trim($path), '/');
        $this->handlerClass = $handlerClass;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function handlerClass(): string
    {
        return $this->handlerClass;
    }
}

