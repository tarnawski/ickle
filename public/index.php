<?php

use App\Integration\Symfony\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv(false))->loadEnv(dirname(__DIR__) . '/src/Integration/Symfony/.env');

$kernel = new Kernel();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
