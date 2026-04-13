<?php

declare(strict_types=1);

namespace Anateje\Contracts;

interface AuditLogger
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, mixed> $meta
     */
    public function log(
        string $modulo,
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        array $before = [],
        array $after = [],
        array $meta = []
    ): void;
}
