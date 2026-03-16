# Restaurant Menu Management API

A RESTful API built with Laravel for managing restaurants and their menu items. Supports token-based authentication, cursor pagination, and per-owner data isolation.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 (PHP 8.5) |
| Authentication | Laravel Sanctum v4 (token-based) |
| Database | MySQL 8.4 (via Docker) |
| Cache / Queue | Redis (Alpine) |
| Local Dev | Laravel Sail (Docker) |
| Testing | PHPUnit (SQLite in-memory) |

---

## Setup Instructions

### Prerequisites

- Docker Desktop
- PHP 8.2+ & Composer (only needed to install Sail initially)

### 1. Install dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

The default `.env` is already configured for Sail (`DB_HOST=mysql`, `REDIS_HOST=redis`).

### 3. Start Docker services

```bash
./vendor/bin/sail up -d
```

### 4. Run migrations and seed data

```bash
./vendor/bin/sail artisan migrate --seed
```

This creates two restaurants with 5 menu items each, owned by:
- **Email:** `test@example.com`
- **Password:** `password`

### 5. Run tests

Tests use SQLite in-memory — no Docker required:

```bash
php artisan test
```

---

## API Reference

All responses follow this structure:

```json
{
    "status": "success | failed",
    "message": "...",
    "data": { ... }
}
```

Paginated list responses nest items and cursor metadata inside `data`:

```json
{
    "data": {
        "items": [...],
        "meta": {
            "per_page": 10,
            "has_more": true,
            "next_cursor": "eyJpZCI6MTB...",
            "prev_cursor": null
        }
    }
}
```

### Authentication

| Method | URL | Description |
|---|---|---|
| `POST` | `/api/public/login` | Login and receive a token |
| `POST` | `/api/private/logout` | Revoke current token |

All `/api/private/*` routes require `Authorization: Bearer {token}`.

### Restaurants

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/private/restaurants` | List own restaurants |
| `POST` | `/api/private/restaurants` | Create a restaurant |
| `GET` | `/api/private/restaurants/{id}` | Get a restaurant (with menu items) |
| `PUT` | `/api/private/restaurants/{id}` | Update a restaurant |
| `DELETE` | `/api/private/restaurants/{id}` | Delete a restaurant |

**Query params for list:** `?limit=10` `?cursor=...`

### Menu Items

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/private/restaurants/{id}/menu_items` | List menu items |
| `POST` | `/api/private/restaurants/{id}/menu_items` | Add a menu item |
| `PUT` | `/api/private/menu_items/{id}` | Update a menu item |
| `DELETE` | `/api/private/menu_items/{id}` | Delete a menu item |

**Query params for list:** `?limit=10` `?cursor=...` `?category=main` `?search=chicken`

Valid categories: `appetizer`, `main`, `dessert`, `drink`

---

## Design Decisions

### Ownership scoping
Every restaurant belongs to the authenticated user via a `user_id` foreign key. All queries are scoped to `$request->user()->restaurants()`, so users can never read or modify another user's data. Ownership checks on individual resources are handled by the `AuthorizesOwnership` trait, which returns `403` on violation.

### Traits over inheritance
Shared response formatting (`ApiResponse`) lives in a trait used by the base `Controller`. Ownership authorization (`AuthorizesOwnership`) is a separate opt-in trait, only used by controllers that deal with restaurant-owned resources — keeping the base controller clean for controllers that don't need it.

### Cursor pagination over offset
Cursor pagination (`cursorPaginate`) is used instead of `paginate` because it is more efficient on large datasets (no `COUNT(*)` query) and produces stable results when rows are inserted between requests. The trade-off is no random page access and no total count — which is acceptable for a feed-style list API.

### PHP Enum for categories
Menu item categories use a PHP 8.1 backed enum (`MenuItemCategory`) rather than a plain string constant. This provides type safety, IDE autocomplete, and direct Eloquent cast support, making invalid category values impossible at the model layer.

### Standardized error handling
All API errors (validation, unauthenticated, forbidden, not found) are caught in `bootstrap/app.php` and returned in the same `{ status, message, data }` envelope as success responses, so clients have a single consistent shape to handle.

### SQLite for tests
Tests run against SQLite in-memory instead of the Docker MySQL instance. This keeps the test suite fast and dependency-free — no Sail required to run `php artisan test`.