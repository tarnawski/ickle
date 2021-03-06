<?php

use App\UserInterface\Symfony\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, Content-Type");
$_SERVER['REQUEST_METHOD'] !== 'OPTIONS' ?: exit;

(new Dotenv(false))->loadEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
