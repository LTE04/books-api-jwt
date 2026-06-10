<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class HealthController
{
    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'name' => 'Books REST API',
            'version' => '1.0.0',
        ], JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
