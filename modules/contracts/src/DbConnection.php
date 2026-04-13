<?php

declare(strict_types=1);

namespace Anateje\Contracts;

interface DbConnection
{
    public function getPdo(): \PDO;
}
