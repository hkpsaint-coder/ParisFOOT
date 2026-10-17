<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminActionLogResource\Pages;
use App\Models\AdminActionLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminActionLogResource extends Resource
{
    protected static ?string $model = AdminActionLog::class;
    protected static ?string $navigationLabel = 'Historique';
    protected static ?string $modelLabel = 'action administrateur';
    protected static ?string $pluralModelLabel = 'historique';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->emptyStateHeading('Aucune action enregistrée')->columns([
            TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
            TextColumn::make('user.name')->label('Administrateur'),
            TextColumn::make('action')->badge(),
            TextColumn::make('table_cible')->label('Table'),
            TextColumn::make('enregistrement_id')->label('ID'),
            TextColumn::make('raison')->limit(50),
            TextColumn::make('ip_address')->label('IP'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAdminActionLogs::route('/')];
    }
}