<?php

declare(strict_types=1);

namespace Anateje\Contracts;

interface HttpClient
{
    /**
     * @param array<string, mixed> $payload
     * @param list<string> $headers
     * @return array{ok:bool,status?:int,body?:string,error?:string}
     */
    public function postJson(string $url, array $payload, array $headers = [], int $timeoutSeconds = 15): array;
}

