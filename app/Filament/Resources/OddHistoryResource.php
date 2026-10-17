<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OddHistoryResource\Pages;
use App\Models\OddHistory;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OddHistoryResource extends Resource
{
    protected static ?string $model = OddHistory::class;
    protected static ?string $navigationLabel = 'Évolution des cotes';
    protected static ?string $modelLabel = 'mouvement de cote';
    protected static ?string $pluralModelLabel = 'évolution des cotes';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['odd.match', 'editor']);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('modifie_le', 'desc')
            ->emptyStateHeading('Aucun mouvement de cote')
            ->columns([
                TextColumn::make('modifie_le')->label('Date')->dateTime()->sortable(),
                TextColumn::make('odd.match.equipe_a')->label('Domicile'),
                TextColumn::make('odd.match.equipe_b')->label('Extérieur'),
                TextColumn::make('odd.bookmaker')->label('Bookmaker'),
                TextColumn::make('ancienne_cote_1')->label('1 avant'),
                TextColumn::make('nouvelle_cote_1')->label('1 après'),
                TextColumn::make('ancienne_cote_X')->label('X avant'),
                TextColumn::make('nouvelle_cote_X')->label('X après'),
                TextColumn::make('ancienne_cote_2')->label('2 avant'),
                TextColumn::make('nouvelle_cote_2')->label('2 après'),
                TextColumn::make('editor.name')->label('Modifié par')->placeholder('API'),
                TextColumn::make('raison')->label('Raison')->limit(40),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOddHistories::route('/')];
    }
}