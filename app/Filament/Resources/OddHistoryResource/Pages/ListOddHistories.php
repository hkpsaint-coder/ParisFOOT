<?php

namespace App\Filament\Resources\OddHistoryResource\Pages;

use App\Filament\Resources\OddHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListOddHistories extends ListRecords
{
    protected static string $resource = OddHistoryResource::class;
    protected static ?string $title = 'Évolution des cotes';
}