<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValueBetResource\Pages;
use App\Models\Analysis;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ValueBetResource extends Resource
{
    protected static ?string $model = Analysis::class;
    protected static ?string $navigationLabel = 'Value bets';
    protected static ?string $modelLabel = 'value bet';
    protected static ?string $pluralModelLabel = 'value bets';
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('value_bet_detecte', true);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->emptyStateHeading('Aucune value bet')->emptyStateDescription('Ajoutez une estimation indépendante pour comparer les probabilités au marché.')->columns([
            TextColumn::make('match.equipe_a')->label('Domicile'),
            TextColumn::make('match.equipe_b')->label('Extérieur'),
            TextColumn::make('odd.bookmaker')->label('Bookmaker'),
            TextColumn::make('proba_1')->label('Marché 1')->suffix('%'),
            TextColumn::make('proba_X')->label('Marché X')->suffix('%'),
            TextColumn::make('proba_2')->label('Marché 2')->suffix('%'),
            TextColumn::make('probabilites_estimees')->label('Estimations')->formatStateUsing(fn ($state): string => collect($state ?? [])->map(fn ($value, $key) => $key.': '.number_format((float) $value, 1).'%')->join(' | ')),
            TextColumn::make('created_at')->label('Détectée')->since(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListValueBets::route('/')];
    }
}