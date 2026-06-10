<?php

use App\Auth\JwtService;
use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Controllers\HealthController;
use App\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\BookRepository;
use App\Repositories\UserRepository;
use Slim\App;

return function (App $app): void {
    $pdo  = Database::get();
    $jwt  = new JwtService();
    $auth = new AuthMiddleware($jwt);

    $bookCtrl = new BookController(new BookRepository($pdo));
    $authCtrl = new AuthController(new UserRepository($pdo), $jwt);

    // ── Health ────────────────────────────────────────────────────
    $app->get('/', [HealthController::class, 'index']);

    // ── Auth (public) ─────────────────────────────────────────────
    $app->post('/auth/register', [$authCtrl, 'register']);
    $app->post('/auth/login',    [$authCtrl, 'login']);

    // ── Auth (protected) ──────────────────────────────────────────
    $app->get('/auth/me', [$authCtrl, 'me'])->add($auth);

    // ── Books: public read ────────────────────────────────────────
    $app->get('/api/books',       [$bookCtrl, 'index']);
    $app->get('/api/books/{id}',  [$bookCtrl, 'show']);

    // ── Books: protected write (requires valid JWT) ───────────────
    $app->group('/api/books', function ($group) use ($bookCtrl): void {
        $group->post  ('',       [$bookCtrl, 'create']);   // any authenticated user
        $group->put   ('/{id}',  [$bookCtrl, 'update']);   // any authenticated user
        $group->delete('/{id}',  [$bookCtrl, 'delete']);   // admin only (checked inside controller)
    })->add($auth);
};
