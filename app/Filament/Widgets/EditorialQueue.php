<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PostResource;
use App\Models\Post;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class EditorialQueue extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Editorial Queue')
            ->query(
                Post::query()
                    ->with(['author', 'category'])
                    ->where(function (Builder $query) {
                        $query
                            ->where('status', 'draft')
                            ->orWhere('published_at', '>', now());
                    })
                    ->latest('updated_at')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->placeholder('Uncategorized')
                    ->badge(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->placeholder('Unknown'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                    ]),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Post $record): string => PostResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
