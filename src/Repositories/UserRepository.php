<?php

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Find a user by email (case-insensitive).
     * Returns the full row (including password_hash) for auth use.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role FROM users WHERE email = :e'
        );
        $stmt->execute([':e' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Find a user by ID (no password_hash returned).
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, role FROM users WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Insert a new user and return the new auto-increment ID.
     *
     * @param  string $name  Display name.
     * @param  string $email Unique email.
     * @param  string $hash  Already-hashed password (use password_hash()).
     * @param  string $role  'member' (default) or 'admin'.
     * @return int           New user ID.
     */
    public function create(
        string $name,
        string $email,
        string $hash,
        string $role = 'member'
    ): int {
        $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role)
             VALUES (:n, :e, :h, :r)'
        )->execute([
            ':n' => trim($name),
            ':e' => mb_strtolower(trim($email)),
            ':h' => $hash,
            ':r' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Return true if the given email already exists in the table.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :e');
        $stmt->execute([':e' => mb_strtolower(trim($email))]);
        return (bool) $stmt->fetchColumn();
    }
}
