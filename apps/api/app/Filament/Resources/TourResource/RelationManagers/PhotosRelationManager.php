<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Фотоальбом';

    protected static ?string $modelLabel = 'фото';

    protected static ?string $pluralModelLabel = 'фотографии';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('url')->label('URL изображения')->url()->required()->columnSpanFull(),
            Forms\Components\TextInput::make('alt')->label('Подпись (alt)')->maxLength(255),
            Forms\Components\TextInput::make('position')->label('Порядок')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')->label('Фото')->height(48),
                Tables\Columns\TextColumn::make('alt')->label('Подпись'),
                Tables\Columns\TextColumn::make('position')->label('Порядок')->sortable(),
            ])
            ->reorderable('position')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
