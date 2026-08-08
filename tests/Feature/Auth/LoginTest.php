<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
