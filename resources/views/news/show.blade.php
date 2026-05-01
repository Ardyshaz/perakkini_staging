<x-layouts.news title="{{ $post->title }} | Perak Kini" description="{{ $post->excerpt }}" :sidebar-categories="$sidebarCategories" :sidebar-latest-posts="$sidebarLatestPosts">
    <div class="layout-grid">
        <div>
            <article class="article">
                @if($post->category)
                    <a class="label" href="{{ route('categories.show', $post->category) }}">{{ $post->category->name }}</a>
                @endif
                <h1>{{ $post->title }}</h1>
                <div class="meta">
                    <span>{{ $post->published_at?->format('d M Y, g:i A') }}</span>
                    <span>{{ $post->author?->name }}</span>
                </div>

                @if($post->featured_image)
                    <img class="article-image" src="{{ str_starts_with($post->featured_image, 'http') ? $post->featured_image : asset('storage/'.$post->featured_image) }}" alt="{{ $post->title }}" style="margin-top: 28px" loading="eager" decoding="async">
                @endif

                <div class="article-content">{!! $post->content !!}</div>

                @if($post->tags->isNotEmpty())
                    <div class="tags">
                        @foreach($post->tags as $tag)
                            <span class="tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </article>

            @if($relatedPosts->isNotEmpty())
                <section style="margin-top: 34px">
                    <h2 class="section-title">Related Posts</h2>
                    <div class="related-grid">
                        @foreach($relatedPosts as $relatedPost)
                            @include('news.partials.card', ['post' => $relatedPost, 'showExcerpt' => false])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        @include('news.partials.sidebar')
    </div>
</x-layouts.news>
