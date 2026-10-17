<?php

namespace App\Filament\Widgets;

use App\Models\Analysis;
use App\Models\MatchGame;
use App\Models\Odd;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FootballStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Matchs', MatchGame::count())->description('En base locale')->icon('heroicon-o-trophy'),
            Stat::make('Cotes', Odd::count())->description('Tous bookmakers')->icon('heroicon-o-chart-bar'),
            Stat::make('Value bets du jour', Analysis::where('value_bet_detecte', true)->whereDate('updated_at', today())->count())->description('Détectées aujourd’hui')->icon('heroicon-o-bolt'),
        ];
    }
}