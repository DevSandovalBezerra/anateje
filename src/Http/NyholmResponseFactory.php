<?php

declare(strict_types=1);

namespace Anateje\Http;

use Anateje\Contracts\ResponseFactory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class NyholmResponseFactory implements ResponseFactory
{
    public function json(array $data, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"ok":false,"error":{"code":"JSON_ENCODE_FAILED"}}';
        }

        $headers = array_merge(['content-type' => 'application/json; charset=utf-8'], $headers);
        return new Response($statusCode, $headers, $json);
    }
}

