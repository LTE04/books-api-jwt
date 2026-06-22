<?php

namespace App\Controllers;

use App\Auth\JwtService;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(
        private UserRepository $users,
        private JwtService     $jwt,
    ) {}

    // ----------------------------------------------------------------
    // POST /auth/register
    // ----------------------------------------------------------------

    /**
     * Register a new member account.
     *
     * Body (JSON):
     *   { "name": "...", "email": "...", "password": "..." }
     *
     * Responses:
     *   201 — created, returns { message, user }
     *   400 — validation errors
     *   409 — email already registered
     */
    public function register(Request $request, Response $response): Response
    {
        $body   = (array) $request->getParsedBody();
        $errors = [];

        if (empty($body['name']) || mb_strlen($body['name']) < 2) {
            $errors['name'] = 'min 2 chars';
        }
        if (empty($body['email']) || ! filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'invalid email';
        }
        if (empty($body['password']) || mb_strlen($body['password']) < 6) {
            $errors['password'] = 'min 6 chars';
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 400);
        }

        if ($this->users->emailExists($body['email'])) {
            return $this->json($response, ['error' => 'Email already registered'], 409);
        }

        $id   = $this->users->create(
            $body['name'],
            $body['email'],
            password_hash($body['password'], PASSWORD_DEFAULT)
        );

        return $this->json($response, [
            'message' => 'Registered',
            'user'    => $this->users->findById($id),
        ], 201);
    }

    // ----------------------------------------------------------------
    // POST /auth/login
    // ----------------------------------------------------------------

    /**
     * Authenticate and receive a JWT.
     *
     * Body (JSON):
     *   { "email": "...", "password": "..." }
     *
     * Responses:
     *   200 — { token_type, expires_in, access_token }
     *   401 — invalid credentials
     */
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $user = $this->users->findByEmail($body['email'] ?? '');

        if (! $user || ! password_verify($body['password'] ?? '', $user['password_hash'])) {
            return $this->json($response, ['error' => 'Invalid credentials'], 401);
        }

        $token = $this->jwt->issue(
            (int) $user['id'],
            ['role' => $user['role'], 'email' => $user['email']]
        );

        return $this->json($response, [
            'token_type'   => 'Bearer',
            'expires_in'   => $this->jwt->ttl(),
            'access_token' => $token,
        ]);
    }

    // ----------------------------------------------------------------
    // GET /auth/me    (requires JWT via AuthMiddleware)
    // ----------------------------------------------------------------

    /**
     * Return the currently authenticated user's profile.
     *
     * Responses:
     *   200 — { id, name, email, role }
     *   404 — user no longer in DB (edge case)
     */
    public function me(Request $request, Response $response): Response
    {
        $auth = (array) $request->getAttribute('auth', []);
        $user = $this->users->findById((int) ($auth['sub'] ?? 0));

        return $user
            ? $this->json($response, $user)
            : $this->json($response, ['error' => 'Not found'], 404);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

   private function json(Response $r, $data, int $status = 200): Response { 
    $r->getBody()->write(json_encode( 
        $data, 
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE 
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT 
    )); 
    return $r->withHeader('Content-Type','application/json; charset=utf-8') 
             ->withStatus($status); 
} 
}
