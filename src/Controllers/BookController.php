<?php

namespace App\Controllers;

use App\Repositories\BookRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Validation\Validator;

final class BookController
{
    public function __construct(private BookRepository $books)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $rows = $this->books->all(
            (string) ($params['q'] ?? ''),
            (int) ($params['limit'] ?? 0)
        );

        return $this->json($response, [
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $book = $this->books->find($id);

        return $book
            ? $this->json($response, $book)
            : $this->json($response, ['error' => "Book {$id} not found"], 404);
    }

    public function create(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);
        $errors = (new Validator())
            ->required('title', 'author', 'year')
            ->field('title', Validator::nonEmptyString(200), 'title must be 1-200 chars')
            ->field('author', Validator::nonEmptyString(150), 'author must be 1-150 chars')
            ->field('year', Validator::intRange(1000, (int)date('Y')), 'year must be 1000..now')
            ->field('genre', Validator::nonEmptyString(80), 'genre must be ≤ 80 chars')
            ->validate($body);

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 400);
        }

        $auth = (array)$request->getAttribute('auth', []);

        $id = $this->books->create(
         $body,
         (int)$auth['sub']
        );

    
        return $this->json($response, [
            'message' => 'Book created',
            'data' => $this->books->find($id),
        ], 201)->withHeader('Location', '/api/books/' . $id);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

         $book = $this->books->find($id); 
        if (!$book) return $this->json($s, ['error'=>'Not found'], 404); 
  
        $auth    = (array)$r->getAttribute('auth', []); 
        $isOwner = (int)$book['created_by'] === (int)($auth['sub'] ?? 0); 
        $isAdmin = ($auth['role'] ?? 'member') === 'admin'; 
        if (!$isOwner && !$isAdmin) return $this->json($s, ['error'=>'Forbidden'], 403); 

        $body = (array) ($request->getParsedBody() ?? []);
        $errors = $this->validate($body, requireAll: false);

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 400);
        }

        $this->books->update($id, $body);

        return $this->json($response, [
            'message' => 'Book updated',
            'data' => $this->books->find($id),
        ]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        // Role check — only admins may delete books
        $auth = (array) $request->getAttribute('auth', []);
        if (($auth['role'] ?? 'member') !== 'admin') {
            return $this->json($response, ['error' => 'Admins only'], 403);
        }

        $id = (int) ($args['id'] ?? 0);
        $book = $this->books->find($id);

        if ($book === null) {
            return $this->json($response, ['error' => "Book {$id} not found"], 404);
        }

        $this->books->delete($id);

        return $this->json($response, [
            'message' => 'Book deleted',
            'data' => $book,
        ]);
    }

    private function validate(array $body, bool $requireAll): array
    {
        $errors = [];
        $rules = [
            'title' => fn(mixed $value): bool => is_string($value) && trim($value) !== '',
            'author' => fn(mixed $value): bool => is_string($value) && trim($value) !== '',
            'year' => fn(mixed $value): bool => (
                is_numeric($value) && (int) $value >= 1000 && (int) $value <= (int) date('Y')
            ),
        ];

        foreach ($rules as $field => $check) {
            if ($requireAll && !array_key_exists($field, $body)) {
                $errors[$field] = "{$field} is required";
                continue;
            }

            if (array_key_exists($field, $body) && !$check($body[$field])) {
                $errors[$field] = "{$field} is invalid";
            }
        }

        return $errors;
    }

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
