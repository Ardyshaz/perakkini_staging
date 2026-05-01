<x-layouts.news title="{{ $category->name }} | Perak Kini" :sidebar-categories="$sidebarCategories" :sidebar-latest-posts="$sidebarLatestPosts">
    <div class="layout-grid">
        <div>
            <h1 class="section-title">{{ $category->name }}</h1>
            <div class="post-grid">
                @forelse($posts as $post)
                    @include('news.partials.card', ['post' => $post])
                @empty
                    <div class="empty-state">No published articles in this category yet.</div>
                @endforelse
            </div>
            <div class="pagination">{{ $posts->links() }}</div>
        </div>

        @include('news.partials.sidebar')
    </div>
</x-layouts.news>
