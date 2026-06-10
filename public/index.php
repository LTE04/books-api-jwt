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

(require __DIR__ . '/../src/routes.php')($app);

$app->run();
