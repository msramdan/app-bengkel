# app-bengkel

Aplikasi bengkel **Atha Motor** berbasis Laravel 13.

## Paket

- Laravel Fortify (autentikasi)
- Spatie Laravel Permission (role & permission)
- Yajra DataTables (tabel server-side)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
# Sesuaikan DB di .env (MySQL)
php artisan migrate --seed
php artisan serve
```

## Login Default

| Field | Value |
|-------|-------|
| Email | `admin@athamotor.com` |
| Password | `password` |

## Palette Warna (Atha Motor)

| Token | Hex | Penggunaan |
|-------|-----|------------|
| Merah utama | `#E31E24` | Primary, sidebar active, navbar border |
| Kuning | `#FFD200` | Accent, warning, highlight |
| Hitam | `#1A1A1A` | Sidebar background |
| Abu terang | `#F4F4F4` | Background konten |

## Struktur Layout

Layout admin **custom** (bukan template pihak ketiga):

- Bootstrap **5.3.3** + Bootstrap Icons
- `public/css/atha-admin.css` — tema Atha Motor (light & dark)
- `public/js/admin.js` — sidebar mobile, theme toggle
- `resources/views/layouts/` — header, sidebar, footer
