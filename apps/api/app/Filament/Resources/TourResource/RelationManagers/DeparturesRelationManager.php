<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DeparturesRelationManager extends RelationManager
{
    protected static string $relationship = 'departures';

    protected static ?string $title = 'Даты и цены';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('starts_on')->required(),
            Forms\Components\DatePicker::make('ends_on')->required(),
            Forms\Components\TextInput::make('price_cents')
                ->label('Цена (копейки)')
                ->numeric()
                ->required()
                ->helperText('Например 8900000 = 89 000 ₽'),
            Forms\Components\TextInput::make('currency')->default('RUB')->maxLength(3),
            Forms\Components\TextInput::make('seats_total')->numeric()->default(16)->required(),
            Forms\Components\TextInput::make('seats_available')->numeric()->default(16)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('starts_on')->date(),
                Tables\Columns\TextColumn::make('ends_on')->date(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 0, '.', ' ') . ' ₽'),
                Tables\Columns\TextColumn::make('seats_available'),
                Tables\Columns\TextColumn::make('seats_total'),
            ])
            ->defaultSort('starts_on')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
