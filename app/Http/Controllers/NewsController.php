<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NewsController extends Controller
{
    public function home(): View
    {
        $payload = Cache::remember('news.home.payload', now()->addMinutes(10), function () {
            $featuredPosts = Post::query()
                ->with(['author', 'category'])
                ->latestPublished()
                ->where('is_featured', true)
                ->take(5)
                ->get();

            if ($featuredPosts->isEmpty()) {
                $featuredPosts = Post::query()
                    ->with(['author', 'category'])
                    ->latestPublished()
                    ->take(5)
                    ->get();
            }

            $categorySections = Category::query()
                ->with(['posts' => fn ($query) => $query
                    ->with(['author', 'category'])
                    ->latestPublished()
                    ->take(4)])
                ->withCount(['posts' => fn ($query) => $query->published()])
                ->orderByDesc('posts_count')
                ->get()
                ->filter(fn (Category $category) => $category->posts_count > 0)
                ->take(4);

            $popularPosts = Post::query()
                ->with(['author', 'category'])
                ->popular()
                ->take(5)
                ->get();

            return compact('featuredPosts', 'categorySections', 'popularPosts');
        });

        $latestPosts = Post::query()
            ->with(['author', 'category'])
            ->latestPublished()
            ->paginate(12);

        return view('news.home', [
            ...$payload,
            'latestPosts' => $latestPosts,
            ...$this->sidebarData(),
        ]);
    }

    public function category(Category $category): View
    {
        $posts = $category->posts()
            ->with(['author', 'category'])
            ->latestPublished()
            ->paginate(12);

        return view('news.category', [
            'category' => $category,
            'posts' => $posts,
            ...$this->sidebarData(),
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::query()
            ->with(['author', 'category', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($post->status === 'published' && $post->published_at?->lte(now()), 404);

        $post->increment('views');

        $relatedPosts = Post::query()
            ->with(['author', 'category'])
            ->latestPublished()
            ->whereKeyNot($post->id)
            ->where(function ($query) use ($post) {
                $query->where('category_id', $post->category_id)
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $post->tags->modelKeys()));
            })
            ->take(4)
            ->get();

        return view('news.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            ...$this->sidebarData(),
        ]);
    }

    public function search(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $posts = Post::query()
            ->with(['author', 'category'])
            ->latestPublished()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            }))
            ->paginate(12)
            ->withQueryString();

        return view('news.search', [
            'search' => $search,
            'posts' => $posts,
            ...$this->sidebarData(),
        ]);
    }

    private function sidebarData(): array
    {
        return Cache::remember('news.sidebar', now()->addMinutes(10), fn () => [
            'sidebarCategories' => Category::query()
                ->withCount(['posts' => fn ($query) => $query->published()])
                ->orderBy('name')
                ->get(),
            'sidebarLatestPosts' => Post::query()
                ->with('category')
                ->latestPublished()
                ->take(6)
                ->get(),
        ]);
    }
}
