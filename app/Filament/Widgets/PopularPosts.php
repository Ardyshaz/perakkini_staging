<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PostResource;
use App\Models\Post;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularPosts extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Popular Posts')
            ->query(
                Post::query()
                    ->with(['author', 'category'])
                    ->published()
                    ->orderByDesc('views')
                    ->latest('published_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->square()
                    ->label('Image'),
                Tables\Columns\TextColumn::make('title')
                    ->limit(70)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->placeholder('Uncategorized')
                    ->badge(),
                Tables\Columns\TextColumn::make('views')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->date('M j, Y')
                    ->label('Published'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Post $record): string => PostResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
