<?php

namespace Tests\Feature;

use App\Models\MatchGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPasswordMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_use_and_revoke_a_sanctum_token(): void
    {
        User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret', 'role' => 'admin']);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret',
            'device_name' => 'test-client',
        ])->assertOk()->assertJsonPath('token_type', 'Bearer');

        $token = $response->json('access_token');
        $this->withToken($token)->getJson('/api/matches')->assertOk();
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/matches')->assertUnauthorized();
    }

    public function test_sensitive_route_requires_valid_admin_password_and_logs_action(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret', 'role' => 'admin']);
        Sanctum::actingAs($user);

        $this->postJson('/api/matches', [
            'equipe_a' => 'Paris', 'equipe_b' => 'Marseille', 'date_match' => '2026-10-20 20:00:00',
            'competition' => 'Ligue 1', 'admin_password' => 'wrong',
        ])->assertForbidden();
        $this->assertDatabaseCount('matches', 0);

        $this->postJson('/api/matches', [
            'equipe_a' => 'Paris', 'equipe_b' => 'Marseille', 'date_match' => '2026-10-20 20:00:00',
            'competition' => 'Ligue 1', 'admin_password' => 'secret', 'raison' => 'Saisie initiale',
        ])->assertCreated();

        $this->assertDatabaseCount('matches', 1);
        $this->assertDatabaseHas('admin_action_logs', ['action' => 'ajout_match', 'user_id' => $user->id]);
    }

    public function test_password_attempts_are_limited_to_five_per_minute(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret', 'role' => 'admin']);
        Sanctum::actingAs($user);
        RateLimiter::clear('admin-password:'.$user->id.':127.0.0.1');
        $payload = ['equipe_a' => 'Paris', 'equipe_b' => 'Marseille', 'date_match' => '2026-10-20', 'competition' => 'Ligue 1', 'admin_password' => 'invalid'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/matches', $payload)->assertForbidden();
        }

        $this->postJson('/api/matches', $payload)->assertStatus(429);
    }

    public function test_reads_require_an_authenticated_user(): void
    {
        MatchGame::create(['equipe_a' => 'Paris', 'equipe_b' => 'Marseille', 'date_match' => '2026-10-20', 'competition' => 'Ligue 1', 'source' => 'manuel']);

        $this->getJson('/api/matches')->assertUnauthorized();
    }

    public function test_api_reads_are_restricted_to_admin_roles(): void
    {
        $user = User::make(['name' => 'Client', 'email' => 'client@example.com', 'password' => 'secret', 'role' => 'client']);
        $user->id = 999;
        Sanctum::actingAs($user);

        $this->getJson('/api/matches')->assertForbidden();
    }
}
