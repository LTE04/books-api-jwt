# Books API — Chapter 11: JWT Authentication

Extends the Chapter 10 Books API with:
- `users` table + hashed passwords
- `POST /auth/register` and `POST /auth/login`
- `GET /auth/me` (protected)
- `AuthMiddleware` (PSR-15, Bearer token)
- Protected `POST /PUT /DELETE` on `/api/books`
- Admin-only `DELETE` (role check)

---

## Quick Start

### 1. Copy `.env.example` → `.env` and fill in values
```
JWT_SECRET=  ← generate with: php -r "echo bin2hex(random_bytes(32));"
JWT_TTL=3600
JWT_ISSUER=books-api
DB_HOST=127.0.0.1
DB_NAME=books_api
DB_USER=root
DB_PASS=
```

### 2. Run the schema
```sql
-- In HeidiSQL or mysql CLI:
source sql/schema.sql
```

### 3. Seed demo users
```bash
# Get a hash first
php -r "echo password_hash('password', PASSWORD_DEFAULT);"
```
Then run in MySQL:
```sql
INSERT INTO users (name, email, password_hash, role) VALUES
  ('Demo Admin',  'admin@books.test',  '<hash>', 'admin'),
  ('Demo Member', 'member@books.test', '<hash>', 'member');
```

### 4. Install dependencies
```bash
composer install
```

### 5. Start the server
```bash
php -S localhost:8000 -t public
```

### 6. Run tests via VS Code REST Client
Open `requests.http`, run Test 2 first, copy `access_token`, paste into `@TOKEN`, then run the rest.

---

## New File Summary

| File | Purpose |
|------|---------|
| `src/Auth/JwtService.php` | Issues and verifies JWTs using `firebase/php-jwt` |
| `src/Repositories/UserRepository.php` | DB queries for the `users` table |
| `src/Controllers/AuthController.php` | `/auth/register`, `/auth/login`, `/auth/me` |
| `src/Middleware/AuthMiddleware.php` | PSR-15 Bearer token verifier |
| `src/routes.php` | Updated with protected routes |
| `src/Controllers/BookController.php` | Admin role check added to `delete()` |
| `sql/schema.sql` | Updated with `users` table |
