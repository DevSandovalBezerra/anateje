<?php

declare(strict_types=1);

namespace Anateje\Contracts;

interface RouteProvider
{
    public function routes(): array;
}

