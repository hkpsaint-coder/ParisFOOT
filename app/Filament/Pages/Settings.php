<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Paramètres';
    protected static ?string $title = 'Paramètres';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'bookmakers' => AppSetting::read('odds_api_bookmakers', config('services.odds_api.bookmakers', '')),
            'regions' => AppSetting::read('odds_api_regions', config('services.odds_api.regions', 'fr')),
            'refresh_minutes' => AppSetting::read('odds_fetch_minutes', config('services.odds_api.refresh_minutes', 30)),
            'value_bet_min_edge' => AppSetting::read('value_bet_min_edge', 0),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('api_key_status')
                    ->label('Clé API The Odds API')
                    ->content(config('services.odds_api.key') ? 'Configurée dans .env' : 'Absente : ajoutez ODDS_API_KEY dans .env'),
                TextInput::make('regions')
                    ->label('Régions de bookmakers')
                    ->placeholder('fr')
                    ->helperText('Codes séparés par des virgules, par exemple eu, uk ou us. Ignorées si des bookmakers précis sont renseignés.'),
                TextInput::make('bookmakers')
                    ->label('Clés de bookmakers (facultatif)')
                    ->placeholder('pinnacle,betclic')
                    ->helperText('Clés The Odds API séparées par des virgules. Une liste renseignée prime sur les régions.'),
                TextInput::make('refresh_minutes')
                    ->label('Fréquence de synchronisation (minutes)')
                    ->numeric()->integer()->minValue(5)->maxValue(59)->required(),
                TextInput::make('value_bet_min_edge')
                    ->label('Écart minimal value bet (points de pourcentage)')
                    ->numeric()->minValue(0)->maxValue(50)->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        AppSetting::write('odds_api_bookmakers', trim((string) ($state['bookmakers'] ?? '')));
        AppSetting::write('odds_api_regions', trim((string) ($state['regions'] ?? 'fr')));
        AppSetting::write('odds_fetch_minutes', (int) $state['refresh_minutes']);
        AppSetting::write('value_bet_min_edge', (float) $state['value_bet_min_edge']);

        Notification::make()->title('Paramètres enregistrés')->success()->send();
    }
}