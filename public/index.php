<?php

use App\Middleware\Cors;
use App\Middleware\JsonBodyParser;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$app = AppFactory::create();

$app->add(new JsonBodyParser());
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(new Cors());

$app->add(new App\Middleware\SecurityHeaders());  // ← added FIRST so it runs LAST 
$app->add(new App\Middleware\JsonBodyParser()); 
$app->add(new App\Middleware\Cors()); 

(require __DIR__ . '/../src/routes.php')($app);

$app->run();
