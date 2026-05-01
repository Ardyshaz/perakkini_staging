@props([
    'title' => 'Perak Kini',
    'description' => 'Berita semasa Perak Kini',
    'sidebarCategories' => collect(),
    'sidebarLatestPosts' => collect(),
])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Perak Kini' }}</title>
    <meta name="description" content="{{ $description ?? 'Berita semasa Perak Kini' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="site-shell">
        <header class="brand-header">
            <a href="{{ route('home') }}" class="brand-mark" aria-label="Perak Kini">
                <small>Dekat Dihati</small>
                Perak <span>Kini</span>
            </a>
            <div class="site-title">PERAK KINI</div>
            <div class="site-tagline">DEKAT DI HATI</div>
        </header>

        <nav class="nav-bar">
            <div class="nav-inner">
                <div class="nav-links">
                    <a href="{{ route('home') }}">Home</a>
                    @foreach(($sidebarCategories ?? collect())->take(5) as $navCategory)
                        <a href="{{ route('categories.show', $navCategory) }}">{{ $navCategory->name }}</a>
                    @endforeach
                </div>
                <form class="search-form" action="{{ route('search') }}" method="get">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search">
                    <button type="submit">Search</button>
                </form>
            </div>
        </nav>

        <main class="container">
            @if(($sidebarLatestPosts ?? collect())->isNotEmpty())
                <div class="ticker">
                    <div class="ticker-label">FLASH STORY</div>
                    <div class="ticker-items">
                        @foreach($sidebarLatestPosts as $tickerPost)
                            <a href="{{ route('posts.show', $tickerPost) }}">{{ $tickerPost->title }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>

        <footer class="footer">
            Copyright &copy; {{ now()->year }} Perak Kini. All rights reserved.
        </footer>
    </div>
</body>
</html>
