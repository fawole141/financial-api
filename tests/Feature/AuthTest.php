<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201) // or 200 if you're not returning 201
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'expires_in',
                     'user' => ['id', 'name', 'email']
                 ]);
    } 

    public function test_user_can_login()
{
    // First, create a user in the database
    $user = \App\Models\User::create([
        'name' => 'Login User',
        'email' => 'login@example.com',
        'password' => bcrypt('password'),
    ]);

    // Attempt to log in
    $response = $this->postJson('/api/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    // Assert we receive expected structure
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'access_token',
                 'token_type',
                 'expires_in',
                 'user' => ['id', 'name', 'email']
             ]);
}


}