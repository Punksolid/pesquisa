<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_the_login_form_with_a_new_email_creates_an_account_and_logs_in(): void
    {
        $response = $this->post('/login', [
            'name' => 'Nueva Investigadora',
            'email' => 'nueva@pesquisa.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'nueva@pesquisa.test',
            'name' => 'Nueva Investigadora',
        ]);
    }

    public function test_a_new_account_without_a_name_falls_back_to_the_email_local_part(): void
    {
        $this->post('/login', [
            'email' => 'anon@pesquisa.test',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'anon@pesquisa.test',
            'name' => 'anon',
        ]);
    }

    public function test_an_existing_email_with_the_correct_password_logs_in_without_creating_a_duplicate(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('email', $user->email)->count());
    }

    public function test_an_existing_email_with_the_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_connecting_a_new_mcp_client_auto_provisions_a_user_and_skips_the_consent_screen(): void
    {
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'Test MCP Client',
            ['https://client.example.com/callback'],
            confidential: true,
        );

        $response = $this->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://client.example.com/callback',
            'scope' => 'mcp:use',
            'state' => 'xyz',
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://client.example.com/callback', $location);
        $this->assertStringContainsString('code=', $location);

        $this->assertDatabaseHas('users', ['passport_client_id' => $client->getKey()]);
        $this->assertAuthenticated();
    }

    public function test_reconnecting_the_same_client_id_reuses_the_same_auto_provisioned_user(): void
    {
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'Test MCP Client',
            ['https://client.example.com/callback'],
            confidential: true,
        );

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://client.example.com/callback',
            'scope' => 'mcp:use',
        ]);

        $this->get('/oauth/authorize?'.$query);
        $firstUserId = auth()->id();

        $this->post('/logout');
        $this->assertGuest();

        $this->get('/oauth/authorize?'.$query);

        $this->assertSame(1, User::where('passport_client_id', $client->getKey())->count());
        $this->assertSame($firstUserId, auth()->id());
    }

    public function test_an_already_authenticated_user_is_not_overridden_by_auto_provisioning(): void
    {
        $user = User::factory()->create();

        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'Test MCP Client',
            ['https://client.example.com/callback'],
            confidential: true,
        );

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://client.example.com/callback',
            'scope' => 'mcp:use',
        ]));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('users', ['passport_client_id' => $client->getKey()]);
    }
}
