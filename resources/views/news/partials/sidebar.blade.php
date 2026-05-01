<aside>
    <section class="sidebar-box">
        <h2 class="section-title">Kategori</h2>
        <ul class="list-links">
            @foreach($sidebarCategories as $category)
                <li>
                    <a href="{{ route('categories.show', $category) }}">{{ $category->name }} ({{ $category->posts_count }})</a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="sidebar-box">
        <h2 class="section-title">Berita Lain</h2>
        <ul class="list-links">
            @foreach($sidebarLatestPosts as $latestPost)
                <li>
                    <a href="{{ route('posts.show', $latestPost) }}">{{ $latestPost->title }}</a>
                </li>
            @endforeach
        </ul>
    </section>
</aside>
