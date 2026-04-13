<?php

declare(strict_types=1);

use Anateje\Http\Kernel;
use Anateje\Http\ResponseEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/includes/base_path.php';

$kernel = require dirname(__DIR__, 2) . '/bootstrap/modula.php';
if (!$kernel instanceof Kernel) {
    throw new RuntimeException('Bootstrap inválido: kernel não retornado.');
}

$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$request = $creator->fromGlobals();

$baseUrl = lidergest_base_url();
if ($baseUrl !== '') {
    $path = $request->getUri()->getPath();
    if (str_starts_with($path, $baseUrl . '/')) {
        $request = $request->withUri($request->getUri()->withPath(substr($path, strlen($baseUrl))));
    }
}

$response = $kernel->handle($request);
(new ResponseEmitter())->emit($response);
