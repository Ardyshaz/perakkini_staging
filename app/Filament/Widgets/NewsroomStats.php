<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NewsroomStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $publishedPosts = Post::query()->published()->count();
        $draftPosts = Post::query()->where('status', 'draft')->count();
        $scheduledPosts = Post::query()
            ->where('status', 'published')
            ->where('published_at', '>', now())
            ->count();
        $totalViews = Post::query()->sum('views');

        return [
            Stat::make('Published posts', number_format($publishedPosts))
                ->description('Live on the public site')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($this->publishedSparkline())
                ->color('success'),

            Stat::make('Drafts', number_format($draftPosts))
                ->description($scheduledPosts.' scheduled')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('warning'),

            Stat::make('Total views', number_format($totalViews))
                ->description('Across imported and new posts')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('Newsroom data', number_format(Category::query()->count()).' categories')
                ->description(number_format(Tag::query()->count()).' tags · '.number_format(User::query()->count()).' users')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('gray'),
        ];
    }

    private function publishedSparkline(): array
    {
        return Post::query()
            ->published()
            ->where('published_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('date(published_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->pipe(function ($totals) {
                return collect(range(6, 0))
                    ->map(fn (int $daysAgo) => (int) ($totals[now()->subDays($daysAgo)->toDateString()] ?? 0))
                    ->all();
            });
    }
}
