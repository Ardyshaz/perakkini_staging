<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\ChartWidget;

class PublishingTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Publishing Trend';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected function getData(): array
    {
        $startDate = now()->subDays(13)->startOfDay();
        $totals = Post::query()
            ->published()
            ->where('published_at', '>=', $startDate)
            ->selectRaw('date(published_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $days = collect(range(13, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo));

        return [
            'datasets' => [
                [
                    'label' => 'Published posts',
                    'data' => $days
                        ->map(fn ($day) => (int) ($totals[$day->toDateString()] ?? 0))
                        ->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.16)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $days
                ->map(fn ($day) => $day->format('M j'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
