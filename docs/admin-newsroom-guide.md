# Perak Kini Admin And Newsroom Guide

This guide documents the current Laravel + Filament admin system for Perak Kini. It covers login, dashboard use, staff/team management, editor workflow, and how to publish news articles.

## Quick Access

- Public website: `/`
- Admin dashboard: `/admin`
- Admin login: `/admin/login`
- Default seeded admin account for local development:
  - Email: `admin@perakkini.test`
  - Password: `password`

Change the seeded password before using any non-local environment.

## Current Admin Modules

The admin panel is powered by Filament and is registered in `app/Providers/Filament/AdminPanelProvider.php`.

Current admin sections:

- Dashboard: newsroom statistics, publishing trend chart, editorial queue, and popular posts.
- Posts: create, edit, publish, feature, and delete articles.
- Users: create and manage staff accounts.
- Categories: manage article categories.
- Tags: manage article tags.

## Staff And Team Roles

The `users.role` field currently supports these roles:

- Admin: can access the admin panel. Intended owner of users, posts, categories, tags, and site settings.
- Editor: can access the admin panel. Intended to review, edit, and publish content.
- Writer: can access the admin panel. Intended to draft articles.

Important current behavior: the app checks these roles for admin panel access, but the individual Filament resources do not yet enforce separate permissions per role. At the moment, any user with `admin`, `editor`, or `writer` can enter the admin panel and may see management resources unless resource-level authorization is added.

Recommended permission model:

- Admin: manage users, roles, categories, tags, and all posts.
- Editor: manage categories/tags if allowed by policy, edit all posts, publish posts.
- Writer: create and edit own drafts only, no user management, no publishing unless approved.

## Managing Staff Accounts

1. Log in at `/admin/login`.
2. Open `Users` from the Newsroom navigation group.
3. Click `Create`.
4. Fill in:
   - Name
   - Email
   - Role: `Admin`, `Editor`, or `Writer`
   - Password
5. Save the user.

To update a staff member:

1. Open `Users`.
2. Select the staff member.
3. Update name, email, role, or password.
4. Leave password blank if it should stay unchanged.
5. Save.

## Categories And Tags

Categories and tags are managed from the Taxonomy navigation group.

Use categories for major news sections, for example:

- Politik
- Masyarakat
- Semasa
- Ekonomi
- Sukan

Use tags for cross-cutting topics, people, places, or repeated themes, for example:

- Perak
- Ipoh
- MB Perak
- PRU

When creating a category or tag, the slug is generated from the name during creation. Keep slugs stable after publishing because category and article URLs depend on slugs.

## How To Post A News Article

1. Log in at `/admin/login`.
2. Open `Posts`.
3. Click `Create`.
4. Fill in the article fields:
   - Title: the headline shown to readers.
   - Slug: URL-friendly article path. It is generated from the title on create, but can be edited.
   - Excerpt: short summary for cards, listing pages, and search previews.
   - Content: full article body.
5. Fill in publishing fields:
   - Author: defaults to the logged-in user.
   - Category: select the main news category.
   - Tags: select related topics.
   - Status: choose `Draft` or `Published`.
   - Featured story: turn on if the article should appear in featured homepage areas.
   - Published at: set the publish date/time.
   - Featured image: upload the article image.
6. Save the article.

Publishing rules:

- `Draft` articles are not visible on the public website.
- `Published` articles are visible only when `published_at` is set and is not in the future.
- Article URLs use the slug directly, for example `/{slug}`.
- Category pages use `/category/{category-slug}`.

## Recommended Editorial Workflow

1. Writer creates a post as `Draft`.
2. Writer fills title, excerpt, content, category, tags, and featured image.
3. Editor reviews the draft for accuracy, language, and formatting.
4. Editor sets `Status` to `Published`.
5. Editor verifies `Published at` is correct.
6. Editor toggles `Featured story` only for homepage priority stories.
7. Editor saves and checks the public article URL.

## Public News Pages

The public news routes are defined in `routes/web.php`.

- `/`: homepage, with featured stories, latest posts, category sections, and popular posts.
- `/search?q=keyword`: search page.
- `/category/{category-slug}`: category archive.
- `/{post-slug}`: article detail page.

The homepage and sidebar use short cache entries. Saving or deleting a post clears the main news caches automatically.

## WordPress Migration

The importer command is `php artisan news:import-wordpress`.

Basic flow:

1. Import `perakkin_wp990.sql` into a temporary MySQL/MariaDB database.
2. Add the temporary WordPress database credentials to `.env`.
3. Run the importer with the WordPress table prefix.

Example:

```bash
php artisan news:import-wordpress --prefix=wplgwv_
```

To copy media:

```bash
php artisan storage:link
php artisan news:import-wordpress --prefix=wplgwv_ --copy-media --uploads-path="C:\path\to\wp-content\uploads"
```

## Codebase Review Notes

Current strengths:

- Clear Laravel 12 structure with app code separated from framework/vendor code.
- Filament admin is already mounted at `/admin`.
- News content is modeled with posts, categories, tags, authors, featured images, status, publish date, and views.
- Public routes are simple and SEO-friendly.
- WordPress migration command exists for posts, users, taxonomy, slugs, dates, content, and media.
- Post save/delete clears homepage/sidebar cache.

Recommended fixes before production:

- Add Filament resource policies so writers cannot manage users or publish/delete all content.
- Add tests for admin login, role access, post publishing visibility, slug routing, category pages, and search.
- Add server-side validation or policies around publishing, especially for writer/editor approval flow.
- Confirm uploaded images are served from `storage` after running `php artisan storage:link`.
- Replace the default local admin password immediately outside local development.
- Use MySQL/MariaDB in production so the posts full-text index is available.
- Confirm `APP_DEBUG=false`, queue/cache settings, backups, and HTTPS before launch.

## Suggested Next Implementation Tasks

1. Create Laravel policies for `Post`, `User`, `Category`, and `Tag`.
2. Limit `UserResource` to admins only.
3. Allow writers to create drafts but not publish or delete other users' posts.
4. Allow editors to publish and edit all posts.
5. Add feature tests for the role matrix.
6. Add a cleaner production dashboard with newsroom metrics.
