<x-layouts.news title="Search | Perak Kini" :sidebar-categories="$sidebarCategories" :sidebar-latest-posts="$sidebarLatestPosts">
    <div class="layout-grid">
        <div>
            <h1 class="section-title">Search{{ $search ? ': '.$search : '' }}</h1>
            <div class="post-grid">
                @forelse($posts as $post)
                    @include('news.partials.card', ['post' => $post])
                @empty
                    <div class="empty-state">No articles matched your search.</div>
                @endforelse
            </div>
            <div class="pagination">{{ $posts->links() }}</div>
        </div>

        @include('news.partials.sidebar')
    </div>
</x-layouts.news>
