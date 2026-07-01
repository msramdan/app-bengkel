# app-bengkel

Aplikasi bengkel berbasis **Laravel 13** (PHP 8.3+).

## Persyaratan

- PHP >= 8.3
- Composer
- Node.js & NPM (untuk asset frontend)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Menjalankan

```bash
php artisan serve
```

Aplikasi berjalan di `http://127.0.0.1:8000`.
