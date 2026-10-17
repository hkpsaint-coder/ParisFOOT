<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\MatchGame;
use App\Models\User;
use App\Notifications\ClientResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_client_and_starts_a_session(): void
    {
        $this->post('/client/register', [
            'name' => 'Client Test',
            'email' => 'client@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
            'role' => 'super_admin',
        ])->assertRedirect(route('client.matches.index'));

        $user = User::where('email', 'client@example.com')->firstOrFail();
        $this->assertSame('client', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_an_email_already_in_use_with_different_case(): void
    {
        User::create(['name' => 'Admin', 'email' => 'Admin@Example.com', 'password' => 'secure-password-123', 'role' => 'admin']);

        $this->post(route('client.register.store'), [
            'name' => 'Client Test',
            'email' => 'admin@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_authenticated_client_can_read_match_odds_and_success_probabilities(): void
    {
        $user = User::create(['name' => 'Client', 'email' => 'client@example.com', 'password' => 'secure-password-123', 'role' => 'client']);
        $match = MatchGame::create([
            'equipe_a' => 'Paris', 'equipe_b' => 'Marseille', 'date_match' => now()->addDays(2),
            'competition' => 'Ligue 1', 'source' => 'api', 'external_id' => 'client-match',
        ]);
        $odd = $match->odds()->create(['bookmaker' => 'Bookmaker FR', 'cote_1' => 2.1, 'cote_X' => 3.4, 'cote_2' => 3.6, 'source' => 'api']);
        Analysis::create([
            'match_id' => $match->id, 'odd_id' => $odd->id,
            'proba_1' => 45.4, 'proba_X' => 28.1, 'proba_2' => 26.5,
            'proba_echec_1' => 54.6, 'proba_echec_X' => 71.9, 'proba_echec_2' => 73.5,
            'marge_bookmaker' => 4.8, 'value_bet_detecte' => false,
        ]);

        $this->actingAs($user)->get(route('client.matches.index'))
            ->assertOk()
            ->assertSee('Paris')
            ->assertSee('Marseille')
            ->assertSee('Bookmaker FR')
            ->assertSee('45,4%');
    }

    public function test_client_cannot_access_admin_api_or_filament_panel(): void
    {
        $client = User::create(['name' => 'Client', 'email' => 'client@example.com', 'password' => 'secure-password-123', 'role' => 'client']);
        Sanctum::actingAs($client);

        $this->getJson('/api/matches')->assertForbidden();
        $this->actingAs($client, 'web')->get('/admin')->assertForbidden();
    }

    public function test_admin_account_cannot_sign_in_to_the_client_portal(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secure-password-123', 'role' => 'admin']);

        $this->post(route('client.login.store'), [
            'email' => $admin->email,
            'password' => 'secure-password-123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)->get(route('client.matches.index'))->assertForbidden();
    }

    public function test_client_can_sign_in_with_a_case_insensitive_email_address(): void
    {
        User::create(['name' => 'Client', 'email' => 'client@example.com', 'password' => 'secure-password-123', 'role' => 'client']);

        $this->post(route('client.login.store'), [
            'email' => 'CLIENT@EXAMPLE.COM',
            'password' => 'secure-password-123',
        ])->assertRedirect(route('client.matches.index'));

        $this->assertAuthenticated();
    }

    public function test_client_can_request_a_password_reset_without_revealing_account_existence(): void
    {
        Notification::fake();
        $client = User::create(['name' => 'Client', 'email' => 'client@example.com', 'password' => 'secure-password-123', 'role' => 'client']);

        $response = $this->from(route('client.password.request'))
            ->post(route('client.password.email'), ['email' => 'CLIENT@EXAMPLE.COM'])
            ->assertRedirect(route('client.password.request'))
            ->assertSessionHas('status');

        Notification::assertSentTo($client, ClientResetPassword::class);

        $this->post(route('client.password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('client.password.request'))
            ->assertSessionHas('status', $response->getSession()->get('status'));
    }

    public function test_client_can_reset_password_using_the_email_link_token(): void
    {
        Notification::fake();
        $client = User::create(['name' => 'Client', 'email' => 'client@example.com', 'password' => 'secure-password-123', 'role' => 'client']);
        $this->post(route('client.password.email'), ['email' => $client->email]);

        $token = null;
        Notification::assertSentTo($client, ClientResetPassword::class, function (ClientResetPassword $notification) use (&$token, $client): bool {
            $token = $notification->token;

            return $notification->toMail($client)->actionUrl === route('client.password.reset', ['token' => $token, 'email' => $client->email]);
        });

        $this->post(route('client.password.update'), [
            'token' => $token,
            'email' => $client->email,
            'password' => 'new-secure-password-456',
            'password_confirmation' => 'new-secure-password-456',
        ])->assertRedirect(route('client.login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-secure-password-456', $client->fresh()->password));
    }

    public function test_admin_cannot_use_client_password_recovery(): void
    {
        Notification::fake();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secure-password-123', 'role' => 'admin']);

        $this->post(route('client.password.email'), ['email' => $admin->email])
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);

        $this->post(route('client.password.update'), [
            'token' => 'not-a-valid-token',
            'email' => $admin->email,
            'password' => 'new-secure-password-456',
            'password_confirmation' => 'new-secure-password-456',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('secure-password-123', $admin->fresh()->password));
    }

    public function test_match_list_requires_login(): void
    {
        $this->get(route('client.matches.index'))->assertRedirect(route('client.login'));
    }

    public function test_match_details_also_require_login(): void
    {
        $match = MatchGame::create([
            'equipe_a' => 'Paris', 'equipe_b' => 'Marseille', 'date_match' => now()->addDay(),
            'competition' => 'Ligue 1', 'source' => 'manuel',
        ]);

        $this->get(route('client.matches.show', $match))->assertRedirect(route('client.login'));
    }

    public function test_match_search_rejects_non_string_filters(): void
    {
        $client = User::create(['name' => 'Client', 'email' => 'client@example.com', 'password' => 'secure-password-123', 'role' => 'client']);

        $this->actingAs($client)
            ->get(route('client.matches.index', ['q' => ['Paris'], 'competition' => ['Ligue 1']]))
            ->assertSessionHasErrors(['q', 'competition']);
    }
}
