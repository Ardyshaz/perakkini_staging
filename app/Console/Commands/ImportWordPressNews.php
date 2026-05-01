<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportWordPressNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:import-wordpress
        {--connection=wordpress : Database connection containing the imported WordPress SQL dump}
        {--prefix=wplgwv_ : WordPress table prefix}
        {--uploads-path= : Local path to the old wp-content/uploads directory}
        {--copy-media : Copy WordPress uploads into storage/app/public/wp-content/uploads}
        {--generate-webp : Generate WebP copies for JPG/PNG images when PHP GD supports it}
        {--media-base-url= : Optional URL prefix to replace legacy upload URLs}
        {--dry-run : Count records without writing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import posts, users, categories, tags, and featured images from the WordPress database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = (string) $this->option('connection');
        $prefix = (string) $this->option('prefix');
        $dryRun = (bool) $this->option('dry-run');

        $wp = DB::connection($connection);

        try {
            $postCount = $wp->table($prefix.'posts')
                ->where('post_type', 'post')
                ->whereIn('post_status', ['publish', 'draft', 'future'])
                ->count();
        } catch (QueryException $exception) {
            $this->components->error('Unable to connect to the WordPress database. Check WP_DB_* values in .env, import perakkin_wp990.sql into MySQL first, then rerun this command.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Found {$postCount} WordPress posts on connection [{$connection}].");

        if ($dryRun) {
            return self::SUCCESS;
        }

        $userMap = $this->importUsers($wp, $prefix);
        $categoryMap = $this->importTerms($wp, $prefix, 'category', Category::class);
        $tagMap = $this->importTerms($wp, $prefix, 'post_tag', Tag::class);

        $wp->table($prefix.'posts')
            ->where('post_type', 'post')
            ->whereIn('post_status', ['publish', 'draft', 'future'])
            ->orderBy('ID')
            ->chunk(100, function ($rows) use ($wp, $prefix, $userMap, $categoryMap, $tagMap) {
                DB::transaction(function () use ($rows, $wp, $prefix, $userMap, $categoryMap, $tagMap) {
                    foreach ($rows as $row) {
                        $status = $row->post_status === 'publish' ? 'published' : 'draft';
                        $slug = $this->uniqueSlug(Post::class, $row->post_name ?: $row->post_title, $row->ID);
                        $categoryId = $this->primaryCategoryId($wp, $prefix, (int) $row->ID, $categoryMap);

                        $post = Post::updateOrCreate(
                            ['old_wp_id' => $row->ID],
                            [
                                'user_id' => $userMap[$row->post_author] ?? User::query()->value('id'),
                                'category_id' => $categoryId,
                                'title' => html_entity_decode($row->post_title ?: 'Untitled', ENT_QUOTES | ENT_HTML5),
                                'slug' => $slug,
                                'excerpt' => $this->excerpt($row->post_excerpt, $row->post_content),
                                'content' => $this->cleanContent($row->post_content),
                                'featured_image' => $this->featuredImage($wp, $prefix, (int) $row->ID),
                                'status' => $status,
                                'is_featured' => false,
                                'published_at' => $row->post_date !== '0000-00-00 00:00:00' ? $row->post_date : null,
                            ]
                        );

                        $tagIds = $this->tagIds($wp, $prefix, (int) $row->ID, $tagMap);
                        $post->tags()->sync($tagIds);
                    }
                });
            });

        $this->components->info('WordPress import completed.');

        return self::SUCCESS;
    }

    private function importUsers($wp, string $prefix): array
    {
        $map = [];

        $wp->table($prefix.'users')
            ->orderBy('ID')
            ->get()
            ->each(function ($row, int $index) use (&$map) {
                $user = User::updateOrCreate(
                    ['old_wp_id' => $row->ID],
                    [
                        'name' => $row->display_name ?: $row->user_login,
                        'email' => $row->user_email ?: "wp-user-{$row->ID}@example.invalid",
                        'password' => Hash::make(Str::random(32)),
                        'role' => $index === 0 ? 'admin' : 'writer',
                    ]
                );

                $map[$row->ID] = $user->id;
            });

        return $map;
    }

    private function importTerms($wp, string $prefix, string $taxonomy, string $modelClass): array
    {
        $map = [];

        $wp->table($prefix.'terms as t')
            ->join($prefix.'term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
            ->where('tt.taxonomy', $taxonomy)
            ->select('t.term_id', 't.name', 't.slug', 'tt.description')
            ->orderBy('t.name')
            ->get()
            ->each(function ($row) use (&$map, $modelClass) {
                $values = [
                    'name' => html_entity_decode($row->name, ENT_QUOTES | ENT_HTML5),
                    'slug' => $this->uniqueSlug($modelClass, $row->slug ?: $row->name, $row->term_id),
                ];

                if ($modelClass === Category::class) {
                    $values['description'] = $row->description;
                }

                $term = $modelClass::query()
                    ->where('old_wp_id', $row->term_id)
                    ->first();

                if (! $term) {
                    $term = $modelClass::query()
                        ->where('slug', $values['slug'])
                        ->first();
                }

                if ($term) {
                    unset($values['slug']);

                    if (! $term->old_wp_id) {
                        $values['old_wp_id'] = $row->term_id;
                    }

                    $term->update($values);
                } else {
                    $term = $modelClass::create([
                        ...$values,
                        'old_wp_id' => $row->term_id,
                    ]);
                }

                $map[$row->term_id] = $term->id;
            });

        return $map;
    }

    private function primaryCategoryId($wp, string $prefix, int $postId, array $categoryMap): ?int
    {
        $termId = $wp->table($prefix.'term_relationships as tr')
            ->join($prefix.'term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'category')
            ->orderBy('tt.term_id')
            ->value('tt.term_id');

        return $termId ? ($categoryMap[$termId] ?? null) : null;
    }

    private function tagIds($wp, string $prefix, int $postId, array $tagMap): array
    {
        return $wp->table($prefix.'term_relationships as tr')
            ->join($prefix.'term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'post_tag')
            ->pluck('tt.term_id')
            ->map(fn ($termId) => $tagMap[$termId] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function featuredImage($wp, string $prefix, int $postId): ?string
    {
        $thumbnailId = $wp->table($prefix.'postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', '_thumbnail_id')
            ->value('meta_value');

        if (! $thumbnailId) {
            return null;
        }

        $url = $wp->table($prefix.'posts')
            ->where('ID', $thumbnailId)
            ->where('post_type', 'attachment')
            ->value('guid');

        if (! $url) {
            return null;
        }

        return $this->migrateMediaUrl($url, forContent: false);
    }

    private function cleanContent(?string $content): string
    {
        $content = (string) $content;
        $content = preg_replace('/<!--\s*\/?wp:[^>]*-->/', '', $content) ?? $content;
        $content = preg_replace_callback(
            '~(?P<quote>[\'"])(?P<url>https?://[^\'"]+/wp-content/uploads/[^\'"]+|/wp-content/uploads/[^\'"]+)(?P=quote)~i',
            fn (array $matches) => $matches['quote'].$this->migrateMediaUrl($matches['url'], forContent: true).$matches['quote'],
            $content
        ) ?? $content;
        $content = preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) {
            $tag = $matches[0];

            if (! str_contains($tag, ' loading=')) {
                $tag = preg_replace('/<img\b/i', '<img loading="lazy"', $tag, 1) ?? $tag;
            }

            if (! str_contains($tag, ' decoding=')) {
                $tag = preg_replace('/<img\b/i', '<img decoding="async"', $tag, 1) ?? $tag;
            }

            return $tag;
        }, $content) ?? $content;

        return trim($content);
    }

    private function excerpt(?string $excerpt, ?string $content): ?string
    {
        $source = trim((string) $excerpt) ?: strip_tags($this->cleanContent($content));

        return $source ? Str::limit(html_entity_decode($source, ENT_QUOTES | ENT_HTML5), 220) : null;
    }

    private function uniqueSlug(string $modelClass, string $source, int $oldId): string
    {
        $base = Str::slug(html_entity_decode($source, ENT_QUOTES | ENT_HTML5)) ?: "imported-{$oldId}";
        $slug = $base;
        $counter = 2;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->where(function ($query) use ($oldId) {
                $query
                    ->where('old_wp_id', '!=', $oldId)
                    ->orWhereNull('old_wp_id');
            })
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function migrateMediaUrl(string $url, bool $forContent): string
    {
        $mediaBaseUrl = $this->option('media-base-url');

        if ($mediaBaseUrl) {
            $path = parse_url($url, PHP_URL_PATH);

            return rtrim((string) $mediaBaseUrl, '/').'/'.ltrim((string) $path, '/');
        }

        $relativePath = $this->relativeUploadPath($url);

        if (! $relativePath) {
            return $url;
        }

        if ($this->option('copy-media')) {
            $relativePath = $this->copyUpload($relativePath);
        }

        return $forContent ? '/storage/'.$relativePath : $relativePath;
    }

    private function relativeUploadPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $position = stripos($path, '/wp-content/uploads/');

        if ($position === false) {
            return null;
        }

        return ltrim(substr($path, $position + 1), '/');
    }

    private function copyUpload(string $relativePath): string
    {
        $uploadsPath = $this->option('uploads-path');

        if (! $uploadsPath) {
            return $relativePath;
        }

        $uploadsRelative = Str::after($relativePath, 'wp-content/uploads/');
        $source = rtrim((string) $uploadsPath, DIRECTORY_SEPARATOR.'/').DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadsRelative);
        $target = storage_path('app/public/'.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        if (! File::exists($source)) {
            return $relativePath;
        }

        File::ensureDirectoryExists(dirname($target));
        File::copy($source, $target);

        if ($this->option('generate-webp')) {
            return $this->generateWebp($target, $relativePath) ?: $relativePath;
        }

        return $relativePath;
    }

    private function generateWebp(string $target, string $relativePath): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $create = match ($extension) {
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? 'imagecreatefromjpeg' : null,
            'png' => function_exists('imagecreatefrompng') ? 'imagecreatefrompng' : null,
            default => null,
        };

        if (! $create) {
            return null;
        }

        $image = @$create($target);

        if (! $image) {
            return null;
        }

        $webpTarget = preg_replace('/\.[^.]+$/', '.webp', $target);
        $webpRelativePath = preg_replace('/\.[^.]+$/', '.webp', $relativePath);

        imagewebp($image, $webpTarget, 82);
        imagedestroy($image);

        return File::exists($webpTarget) ? $webpRelativePath : null;
    }
}
