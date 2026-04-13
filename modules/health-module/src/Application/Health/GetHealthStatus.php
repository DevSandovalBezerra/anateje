<?php

declare(strict_types=1);

namespace Anateje\HealthModule\Application\Health;

final class GetHealthStatus
{
    public function execute(): array
    {
        return [
            'status' => 'ok',
            'ts' => date('c'),
        ];
    }
}

