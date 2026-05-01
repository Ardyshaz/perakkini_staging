<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Newsroom';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Article')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create'
                            ? $set('slug', Str::slug($state))
                            : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Textarea::make('excerpt')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('content')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'strike',
                            'link',
                            'bulletList',
                            'orderedList',
                            'blockquote',
                            'h2',
                            'h3',
                            'undo',
                            'redo',
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Publishing')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(auth()->id()),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                        ])
                        ->required()
                        ->default('draft'),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured story'),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->seconds(false)
                        ->default(now()),
                    Forms\Components\FileUpload::make('featured_image')
                        ->image()
                        ->directory('posts')
                        ->imageEditor()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['author', 'category']))
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')->square(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(55)->sortable(),
                Tables\Columns\TextColumn::make('category.name')->sortable(),
                Tables\Columns\TextColumn::make('author.name')->label('Author')->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
                Tables\Columns\TextColumn::make('views')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->colors([
                    'gray' => 'draft',
                    'success' => 'published',
                ]),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ]),
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
