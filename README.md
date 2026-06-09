# Perry — Monitoring Agent Management

[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fd6da6d14-8a85-402d-964d-89caf641ff3b&style=plastic)](https://forge.laravel.com/fullstack/fs-app-01/3233149)

A Laravel management interface for monitoring agents. Agents report PHP, MySQL, Reverb, Redis, and system health checks back to this dashboard.

## Requirements

- PHP 8.4+
- Node.js
- SQLite (testing) / MySQL or PostgreSQL (production)

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Testing

```bash
./vendor/bin/pest
```

Run a specific group:

```bash
./vendor/bin/pest --group=models
./vendor/bin/pest --group=notifications
```

Run with coverage:

```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage
```
