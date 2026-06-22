<?php

namespace App\Middleware;

use App\Auth\JwtService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

/**
 * AuthMiddleware
 *
 * Checks the Authorization: Bearer <token> header.
 * On success, attaches the decoded JWT payload as the "auth" request attribute.
 * On failure, returns 401 JSON with WWW-Authenticate: Bearer.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private JwtService $jwt) {}

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $header = $request->getHeaderLine('Authorization');

        // Expect "Bearer <token>"
        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $this->fail('Missing or malformed token');
        }

        try {
            $payload = $this->jwt->verify($matches[1]);
        } catch (\Throwable $e) {
            error_log('[AuthMiddleware] ' . $e->getMessage() . ' | secret: ' . substr($_ENV['JWT_SECRET'] ?? 'MISSING', 0, 8));
            return $this->fail('Invalid or expired token');
        }

        // Attach decoded payload so controllers can read it via
        //   $req->getAttribute('auth')['sub']   ← user ID
        //   $req->getAttribute('auth')['role']  ← 'member' | 'admin'
        $request = $request->withAttribute('auth', $payload);

        return $handler->handle($request);
    }

    private function fail(string $message): ResponseInterface
    {
        $response = new SlimResponse(401);
        $response->getBody()->write(json_encode(['error' => $message]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('WWW-Authenticate', 'Bearer');
    }
}
