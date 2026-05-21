<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TourResource\Pages;
use App\Filament\Resources\TourResource\RelationManagers;
use App\Models\Tour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    /** @var list<string> */
    private const ADMIN_COLUMNS = [
        'id',
        'slug',
        'title',
        'summary',
        'description',
        'duration_days',
        'route_geojson',
        'cover_url',
        'meta_title',
        'meta_description',
        'published_at',
        'created_at',
        'updated_at',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->select(static::ADMIN_COLUMNS);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tour')->tabs([
                Forms\Components\Tabs\Tab::make('Основное')->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('summary')->required()->rows(3)->maxLength(500),
                    Forms\Components\MarkdownEditor::make('description')->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('duration_days')->numeric()->required()->minValue(1)->maxValue(30),
                    Forms\Components\Select::make('categories')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->preload(),
                    Forms\Components\TextInput::make('cover_url')->url()->label('Обложка (URL)'),
                    Forms\Components\DateTimePicker::make('published_at')->label('Опубликован'),
                ])->columns(2),
                Forms\Components\Tabs\Tab::make('Маршрут')->schema([
                    Forms\Components\Textarea::make('route_geojson')
                        ->label('GeoJSON маршрута')
                        ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '')
                        ->dehydrateStateUsing(function (?string $state) {
                            if (! $state) {
                                return null;
                            }
                            $decoded = json_decode($state, true);

                            return is_array($decoded) ? $decoded : null;
                        })
                        ->rows(12)
                        ->columnSpanFull()
                        ->helperText('LineString + waypoints. Генерация LLM заполняет автоматически.'),
                ]),
                Forms\Components\Tabs\Tab::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(120),
                    Forms\Components\Textarea::make('meta_description')->maxLength(500)->rows(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')->label('')->circular(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('duration_days')->label('Дней')->sortable(),
                Tables\Columns\TextColumn::make('categories.name')->badge(),
                Tables\Columns\IconColumn::make('published_at')->label('Live')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('published_at')
                    ->label('Опубликован')
                    ->nullable()
                    ->trueLabel('Да')
                    ->falseLabel('Черновик')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('published_at'),
                        false: fn ($q) => $q->whereNull('published_at'),
                    ),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
            RelationManagers\DeparturesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
