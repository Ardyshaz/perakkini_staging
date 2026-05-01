<x-layouts.news title="Perak Kini" :sidebar-categories="$sidebarCategories" :sidebar-latest-posts="$sidebarLatestPosts">
    <div class="layout-grid">
        <div>
            @if($featuredPosts->isNotEmpty())
                <section>
                    <h1 class="section-title">Main Story</h1>
                    <div class="hero-grid">
                        @foreach($featuredPosts as $post)
                            @include('news.partials.card', [
                                'post' => $post,
                                'large' => $loop->first,
                                'showExcerpt' => $loop->first,
                            ])
                        @endforeach
                    </div>
                </section>
            @endif

            <section style="margin-top: 34px">
                <h2 class="section-title">Latest News</h2>
                <div class="post-grid">
                    @forelse($latestPosts as $post)
                        @include('news.partials.card', ['post' => $post])
                    @empty
                        <div class="empty-state">No published articles yet.</div>
                    @endforelse
                </div>
                <div class="pagination">{{ $latestPosts->links() }}</div>
            </section>

            @foreach($categorySections as $section)
                <section style="margin-top: 34px">
                    <h2 class="section-title">{{ $section->name }}</h2>
                    <div class="post-grid">
                        @foreach($section->posts as $post)
                            @include('news.partials.card', ['post' => $post, 'showExcerpt' => false])
                        @endforeach
                    </div>
                </section>
            @endforeach

            @if($popularPosts->isNotEmpty())
                <section style="margin-top: 34px">
                    <h2 class="section-title">Popular</h2>
                    <div class="post-grid">
                        @foreach($popularPosts as $post)
                            @include('news.partials.card', ['post' => $post, 'showExcerpt' => false])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        @include('news.partials.sidebar')
    </div>
</x-layouts.news>
