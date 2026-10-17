<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\MatchGame;
use App\Models\Odd;
use App\Services\OddsFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
use Tests\TestCase;

class OddsFetcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_events_and_persists_odds_and_analysis(): void
    {
        config(['services.odds_api.key' => 'test-key']);
        config(['services.odds_api.sport' => 'soccer_france_ligue_one']);
        config(['services.odds_api.bookmakers' => 'test_bookmaker']);
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/sports/soccer_france_ligue_one/odds?')) {
                return Http::response([[ 
                    'id' => 12345,
                    'sport_key' => 'soccer_france_ligue_one',
                    'sport_title' => 'Ligue 1 - France',
                    'home_team' => 'Paris',
                    'away_team' => 'Marseille',
                    'commence_time' => '2026-10-20T20:00:00Z',
                    'bookmakers' => [[
                            'key' => 'test_bookmaker',
                            'title' => 'Test Bookmaker',
                            'markets' => [[
                                'key' => 'h2h',
                                'outcomes' => [
                                    ['name' => 'Paris', 'price' => 2.10],
                                    ['name' => 'Draw', 'price' => 3.40],
                                    ['name' => 'Marseille', 'price' => 3.60],
                                ],
                            ]],
                    ]],
                ]], 200);
            }

            return Http::response([], 404);
        });

        $count = app(OddsFetcher::class)->fetch(force: true);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('matches', ['external_id' => '12345', 'source' => 'api']);
        $this->assertDatabaseHas('odds', ['bookmaker' => 'Test Bookmaker', 'cote_1' => 2.1]);
        $this->assertDatabaseCount('analyses', 1);
        $this->assertInstanceOf(Analysis::class, Analysis::first());
        $this->assertSame(1, MatchGame::count());
        $this->assertSame(1, Odd::count());
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v4/sports/soccer_france_ligue_one/odds')
            && $request['markets'] === 'h2h'
            && $request['bookmakers'] === 'test_bookmaker'
            && $request['oddsFormat'] === 'decimal');
    }

    public function test_it_records_external_price_movements(): void
    {
        config(['services.odds_api.key' => 'test-key']);
        config(['services.odds_api.sport' => 'soccer_france_ligue_one']);
        config(['services.odds_api.bookmakers' => 'test_bookmaker']);
        $currentHomePrice = '2.10';
        Http::fake(function (Request $request) use (&$currentHomePrice) {
            if (str_contains($request->url(), '/sports/soccer_france_ligue_one/odds?')) {
                return Http::response([[
                    'id' => 12345,
                    'sport_key' => 'soccer_france_ligue_one', 'sport_title' => 'Ligue 1 - France',
                    'home_team' => 'Paris', 'away_team' => 'Marseille', 'commence_time' => '2026-10-20T20:00:00Z',
                    'bookmakers' => [[
                        'key' => 'test_bookmaker', 'title' => 'Test Bookmaker',
                        'markets' => [['key' => 'h2h', 'outcomes' => [
                            ['name' => 'Paris', 'price' => $currentHomePrice], ['name' => 'Draw', 'price' => '3.40'], ['name' => 'Marseille', 'price' => '3.60'],
                        ]]],
                    ]],
                ]], 200);
            }

            return Http::response([], 404);
        });

        app(OddsFetcher::class)->fetch(force: true);
        $currentHomePrice = '2.25';
        app(OddsFetcher::class)->fetch(force: true);

        $this->assertDatabaseHas('odds_history', [
            'ancienne_cote_1' => 2.1,
            'nouvelle_cote_1' => 2.25,
            'raison' => 'Mise à jour The Odds API',
        ]);
    }

    public function test_it_does_not_overwrite_a_manual_odd_during_refresh(): void
    {
        config(['services.odds_api.key' => 'test-key']);
        config(['services.odds_api.sport' => 'soccer_france_ligue_one']);
        config(['services.odds_api.bookmakers' => 'test_bookmaker']);
        Http::fake(function (Request $request) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/sports/soccer_france_ligue_one/odds')) {
                return Http::response([[
                    'id' => 67890,
                    'sport_key' => 'soccer_france_ligue_one', 'sport_title' => 'Ligue 1 - France',
                    'home_team' => 'Paris', 'away_team' => 'Marseille', 'commence_time' => '2026-10-20T20:00:00Z',
                    'bookmakers' => [[
                        'key' => 'test_bookmaker', 'title' => 'Test Bookmaker',
                        'markets' => [['key' => 'h2h', 'outcomes' => [
                            ['name' => 'Paris', 'price' => '1.50'], ['name' => 'Draw', 'price' => '4.00'], ['name' => 'Marseille', 'price' => '6.00'],
                        ]]],
                    ]],
                ]], 200);
            }

            return Http::response([], 404);
        });

        $match = MatchGame::create([
            'equipe_a' => 'Paris', 'equipe_b' => 'Marseille', 'date_match' => '2026-10-20 20:00:00',
            'competition' => 'Ligue 1', 'source' => 'api', 'external_id' => '67890',
        ]);
        $odd = $match->odds()->create([
            'bookmaker' => 'Test Bookmaker', 'cote_1' => 2.10, 'cote_X' => 3.40, 'cote_2' => 3.60,
            'source' => 'manuel', 'is_modified' => false,
        ]);

        app(OddsFetcher::class)->fetch(force: true);

        $this->assertDatabaseHas('odds', ['id' => $odd->id, 'cote_1' => 2.10, 'source' => 'manuel']);
    }
}