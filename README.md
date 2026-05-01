# Perak Kini Laravel News Portal

Lightweight Laravel rebuild of the WordPress-based `perakkini.com` news portal.

## Stack

- Laravel 12
- Filament 3 admin panel at `/admin`
- Blade + Tailwind/Vite frontend
- SQLite for local development, MySQL/MariaDB recommended for production

## Local Setup

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Seeded admin account:

- Email: `admin@perakkini.test`
- Password: `password`

## Admin And Newsroom Documentation

See [docs/admin-newsroom-guide.md](docs/admin-newsroom-guide.md) for admin login, dashboard modules, staff/team roles, editor workflow, and step-by-step article publishing instructions.

## WordPress Migration

Import `perakkin_wp990.sql` into a temporary MySQL database, then set these values in `.env`:

```dotenv
WP_DB_HOST=127.0.0.1
WP_DB_PORT=3306
WP_DB_DATABASE=perakkin_wp990
WP_DB_USERNAME=root
WP_DB_PASSWORD=
```

Run:

```bash
php artisan news:import-wordpress --prefix=wplgwv_
```

To copy old images into Laravel storage:

```bash
php artisan storage:link
php artisan news:import-wordpress --prefix=wplgwv_ --copy-media --uploads-path="C:\path\to\wp-content\uploads"
```

Optional WebP generation for copied JPG/PNG images:

```bash
php artisan news:import-wordpress --prefix=wplgwv_ --copy-media --generate-webp --uploads-path="C:\path\to\wp-content\uploads"
```

The importer migrates posts, categories, tags, users, slugs, publish status, dates, featured images, inline content images, and tag relationships. It intentionally ignores comments, spam, and plugin metadata.

Legacy SEO URLs are preserved as `/{slug}`. Make sure imported slugs remain unchanged before going live.

## Production Notes

- Point the web root to `public/`.
- Run `php artisan storage:link` for uploaded featured images.
- Enable PHP `zip` extension for Filament import/export dependencies.
- Use MySQL/MariaDB to get the `posts.title/content` full-text index.
- Set `APP_DEBUG=false`.
- Run `php artisan optimize`.
- Keep homepage/sidebar cache enabled; post/category/tag saves clear the news cache automatically.
- Filament login is CSRF-protected and rate-limited by Livewire/Filament middleware.
