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

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('url')->url()->required()->columnSpanFull(),
            Forms\Components\TextInput::make('alt')->maxLength(255),
            Forms\Components\TextInput::make('position')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')->height(48),
                Tables\Columns\TextColumn::make('alt'),
                Tables\Columns\TextColumn::make('position')->sortable(),
            ])
            ->reorderable('position')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
