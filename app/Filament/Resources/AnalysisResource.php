<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnalysisResource\Pages;
use App\Models\Analysis;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AnalysisResource extends Resource
{
    protected static ?string $model = Analysis::class;
    protected static ?string $navigationLabel = 'Analyses';
    protected static ?string $modelLabel = 'analyse';
    protected static ?string $pluralModelLabel = 'analyses';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('match.equipe_a')->label('Domicile'),
            TextColumn::make('match.equipe_b')->label('Extérieur'),
            TextColumn::make('odd.bookmaker')->label('Bookmaker'),
            TextColumn::make('proba_1')->label('1 %')->suffix('%'),
            TextColumn::make('proba_X')->label('X %')->suffix('%'),
            TextColumn::make('proba_2')->label('2 %')->suffix('%'),
            TextColumn::make('marge_bookmaker')->label('Marge')->suffix('%'),
            TextColumn::make('value_bet_detecte')->label('Value Bet')->badge(),
        ])->emptyStateHeading('Aucune analyse')->emptyStateDescription('Les analyses sont générées automatiquement à partir des cotes.')
            ->filters([TernaryFilter::make('value_bet_detecte')->label('Value bet détectée')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAnalyses::route('/')];
    }
}