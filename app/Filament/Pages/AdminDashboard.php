<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    protected static bool $isDiscovered = false;
    protected static ?string $title = 'Tableau de bord';
    protected static ?string $navigationLabel = 'Tableau de bord';
}