<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_root_redirects_to_admin(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/admin');
    }

    public function test_user_can_register(): void
    {
        $email = 'newuser_' . time() . '@example.com';

        $response = $this->postJson('/api/v1/register', [
            'name' => 'مستخدم جديد',
            'email' => $email,
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'token',
                     'access_token',
                     'token_type',
                     'user' => ['id', 'name', 'email', 'role'],
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => 'user',
        ]);
    }

    public function test_registration_validation_fails(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'مستخدم مسجل',
            'email' => 'testlogin_' . time() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'token',
                     'access_token',
                     'user' => ['id', 'name', 'email'],
                 ]);
    }

    public function test_user_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                 ]);
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::create([
            'name' => 'صاحب الحساب',
            'email' => 'profile_' . time() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        // Fetch user
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/user');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'user' => [
                         'id' => $user->id,
                         'email' => $user->email,
                     ],
                 ]);

        // Logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                               ->postJson('/api/v1/logout');

        $logoutResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                       ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        // Clear guard state in memory for testing
        $this->app['auth']->forgetGuards();

        // After logout, token is revoked
        $protectedResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                                  ->getJson('/api/v1/user');

        $protectedResponse->assertStatus(401);
    }
}
