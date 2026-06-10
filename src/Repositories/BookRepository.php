<?php

namespace App\Repositories;

use PDO;

final class BookRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(string $query = '', int $limit = 0): array
    {
        $sql = 'SELECT * FROM books';
        $args = [];

        if ($query !== '') {
            $sql .= ' WHERE title LIKE :q_title OR author LIKE :q_author';
            $args[':q_title'] = '%' . $query . '%';
            $args[':q_author'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY id ASC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($args);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM books WHERE id = :id');
        $statement->execute([':id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $book): int
    {
        $sql = 'INSERT INTO books (title, author, year, genre)
                VALUES (:title, :author, :year, :genre)';

        $this->pdo->prepare($sql)->execute([
            ':title' => trim((string) $book['title']),
            ':author' => trim((string) $book['author']),
            ':year' => (int) $book['year'],
            ':genre' => trim((string) ($book['genre'] ?? 'Uncategorised')),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $book): int
    {
        $sets = [];
        $args = [':id' => $id];

        foreach (['title', 'author', 'genre'] as $field) {
            if (array_key_exists($field, $book)) {
                $sets[] = "{$field} = :{$field}";
                $args[":{$field}"] = trim((string) $book[$field]);
            }
        }

        if (array_key_exists('year', $book)) {
            $sets[] = 'year = :year';
            $args[':year'] = (int) $book['year'];
        }

        if ($sets === []) {
            return 0;
        }

        $sql = 'UPDATE books SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($args);

        return $statement->rowCount();
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM books WHERE id = :id');
        $statement->execute([':id' => $id]);

        return $statement->rowCount() === 1;
    }
}
