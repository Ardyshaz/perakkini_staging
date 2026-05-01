<article class="post-card">
    <a href="{{ route('posts.show', $post) }}">
        @if($post->featured_image)
            <img src="{{ str_starts_with($post->featured_image, 'http') ? $post->featured_image : asset('storage/'.$post->featured_image) }}" alt="{{ $post->title }}" loading="lazy" decoding="async">
        @else
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='500'%3E%3Crect width='100%25' height='100%25' fill='%23262626'/%3E%3Ctext x='50%25' y='50%25' fill='%23ffd400' font-family='Arial' font-size='42' font-weight='700' text-anchor='middle'%3EPERAK KINI%3C/text%3E%3C/svg%3E" alt="" loading="lazy" decoding="async">
        @endif
    </a>
    <div class="card-body">
        @if($post->category)
            <a class="label" href="{{ route('categories.show', $post->category) }}">{{ $post->category->name }}</a>
        @endif
        <h2 class="post-title {{ $large ?? false ? 'large' : '' }}">
            <a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a>
        </h2>
        <div class="meta">
            <span>{{ $post->published_at?->diffForHumans() }}</span>
            <span>{{ $post->author?->name }}</span>
            @if(($post->views ?? 0) > 0)
                <span>{{ number_format($post->views) }} views</span>
            @endif
        </div>
        @if($showExcerpt ?? true)
            <p class="excerpt">{{ $post->excerpt ?: Str::limit(strip_tags($post->content), 150) }}</p>
        @endif
    </div>
</article>
