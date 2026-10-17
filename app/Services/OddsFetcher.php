<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\Odd;
use App\Models\AppSetting;
use App\Models\OddHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OddsFetcher
{
    public function __construct(private readonly AnalysisService $analysisService)
    {
    }

    public function fetch(bool $force = false): int
    {
        $apiKey = config('services.odds_api.key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('La variable ODDS_API_KEY n’est pas configurée.');
        }

        $sport = config('services.odds_api.sport', 'soccer_france_ligue_one');
        $configuredBookmakers = AppSetting::read('odds_api_bookmakers');
        $configuredRegions = AppSetting::read('odds_api_regions');
        $cacheKey = 'the-odds-api:'.sha1(implode('|', [
            $sport,
            $configuredBookmakers ?: config('services.odds_api.bookmakers', ''),
            $configuredRegions ?: config('services.odds_api.regions', 'fr'),
        ]));
        if ($force) {
            Cache::forget($cacheKey);
        }

        $snapshot = Cache::remember($cacheKey, now()->addMinutes(25), fn () => $this->fetchSnapshot($apiKey));
        $count = $this->persistSnapshot($snapshot);

        Log::info('Cotes The Odds API synchronisées.', ['nombre_cotes' => $count]);

        return $count;
    }

    private function fetchSnapshot(string $apiKey): array
    {
        $baseUrl = rtrim(config('services.odds_api.url'), '/');
        $configuredBookmakers = AppSetting::read('odds_api_bookmakers');
        if (blank($configuredBookmakers)) {
            $configuredBookmakers = config('services.odds_api.bookmakers', '');
        }
        $bookmakers = array_values(array_filter(array_map('trim', explode(',', (string) $configuredBookmakers))));
        $parameters = [
            'apiKey' => trim($apiKey),
            'markets' => 'h2h',
            'oddsFormat' => config('services.odds_api.odds_format', 'decimal'),
            'dateFormat' => 'iso',
        ];
        if ($bookmakers !== []) {
            $parameters['bookmakers'] = implode(',', $bookmakers);
        } else {
            $parameters['regions'] = AppSetting::read('odds_api_regions') ?: config('services.odds_api.regions', 'fr');
        }

        $events = Http::acceptJson()->timeout(30)
            ->get($baseUrl.'/sports/'.rawurlencode(config('services.odds_api.sport', 'soccer_france_ligue_one')).'/odds', $parameters)
            ->throw()->json();

        if (! is_array($events)) {
            throw new RuntimeException('The Odds API a renvoyé une liste de matchs invalide.');
        }

        return ['events' => $events, 'odds' => []];
    }

    private function persistSnapshot(array $snapshot): int
    {
        $oddsByEvent = $snapshot['odds'] ?? [];
        $count = 0;

        foreach ($snapshot['events'] ?? [] as $event) {
            if (! is_array($event) || empty($event['home_team']) || empty($event['away_team']) || empty($event['commence_time']) || ! isset($event['id'])) {
                continue;
            }

            $match = MatchGame::updateOrCreate(
                ['external_id' => (string) $event['id']],
                [
                    'equipe_a' => $event['home_team'],
                    'equipe_b' => $event['away_team'],
                    'date_match' => $event['commence_time'],
                    'competition' => $event['sport_title'] ?? $event['sport_key'] ?? 'Football',
                    'source' => 'api',
                ],
            );

            foreach ($this->extractBookmakers($event) as $bookmaker => $prices) {
                $odd = Odd::firstOrNew(['match_id' => $match->id, 'bookmaker' => $bookmaker]);
                $canUpdate = ! $odd->exists || ($odd->source === 'api' && ! $odd->is_modified);
                if ($canUpdate) {
                    $previous = $odd->exists ? $odd->only(['cote_1', 'cote_X', 'cote_2']) : null;
                    $odd->fill([...$prices, 'source' => 'api'])->save();

                    if ($previous !== null && collect(['cote_1', 'cote_X', 'cote_2'])->contains(fn (string $field): bool => (float) $previous[$field] !== (float) $prices[$field])) {
                        OddHistory::create([
                            'odd_id' => $odd->id,
                            'ancienne_cote_1' => $previous['cote_1'],
                            'nouvelle_cote_1' => $prices['cote_1'],
                            'ancienne_cote_X' => $previous['cote_X'],
                            'nouvelle_cote_X' => $prices['cote_X'],
                            'ancienne_cote_2' => $previous['cote_2'],
                            'nouvelle_cote_2' => $prices['cote_2'],
                            'raison' => 'Mise à jour The Odds API',
                        ]);
                    }
                }
                $this->analysisService->update($odd, $odd->analyses()->first()?->probabilites_estimees);
                $count++;
            }
        }

        return $count;
    }

    private function extractBookmakers(array $event): array
    {
        $result = [];
        foreach ($event['bookmakers'] ?? [] as $bookmaker) {
            $bookmakerName = $bookmaker['title'] ?? $bookmaker['key'] ?? 'Inconnu';
            $markets = $bookmaker['markets'] ?? [];
            foreach ($markets as $market) {
                if (strtolower($market['key'] ?? '') !== 'h2h') {
                    continue;
                }

                $outcomes = collect($market['outcomes'] ?? [])->keyBy('name');
                $home = data_get($outcomes->get($event['home_team']), 'price');
                $draw = data_get($outcomes->get('Draw'), 'price');
                $away = data_get($outcomes->get($event['away_team']), 'price');
                if (is_numeric($home) && is_numeric($draw) && is_numeric($away)) {
                    $result[$bookmakerName] = ['cote_1' => (float) $home, 'cote_X' => (float) $draw, 'cote_2' => (float) $away];
                }
            }
        }

        return $result;
    }
}