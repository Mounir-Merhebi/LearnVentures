<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth as AuthFacade;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure JWT package has a secret during tests
        Config::set('jwt.secret', 'test_secret_key_for_ci');
        Config::set('jwt.algo', 'HS256');
        // If your install uses keys instead of shared secret, uncomment:
        // Config::set('jwt.keys.public', base_path('tests/fixtures/jwtRS256.key.pub'));
        // Config::set('jwt.keys.private', base_path('tests/fixtures/jwtRS256.key'));
    }

    private function mockJwtForRegister(): void
    {
        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->andReturn('fake_token_register');
    }

    private function mockJwtForLogin(User $user): void
    {
        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andReturn('fake_token_login');

        AuthFacade::shouldReceive('user')
            ->andReturn($user);
    }

    #[Test]
    public function user_can_register_successfully(): void
    {
        $this->mockJwtForRegister();

        $payload = [
            'name' => 'Maroun',
            'email' => 'maroun@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v0.1/guest/register', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('payload.email', 'maroun@example.com')
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'payload' => [
                         'id',
                         'name',
                         'email',
                         'token',
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'maroun@example.com',
            'name' => 'Maroun',
            'role' => 'Student',
        ]);
    }

    #[Test]
    public function registration_requires_all_fields(): void
    {
        $response = $this->postJson('/api/v0.1/guest/register', []);

        $response->assertStatus(400)
                 ->assertJsonPath('success', false);
    }

    #[Test]
    public function user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'name' => 'Maroun',
            'email' => 'maroun@example.com',
            'password' => Hash::make('password123'),
            'role' => 'Student',
        ]);

        $this->mockJwtForLogin($user);

        $response = $this->postJson('/api/v0.1/guest/login', [
            'email' => 'maroun@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('payload.email', 'maroun@example.com')
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'payload' => [
                         'id',
                         'name',
                         'email',
                         'token',
                     ]
                 ]);
    }

    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'name' => 'Maroun',
            'email' => 'maroun@example.com',
            'password' => Hash::make('password123'),
            'role' => 'Student',
        ]);

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andReturn(false);

        $response = $this->postJson('/api/v0.1/guest/login', [
            'email' => 'maroun@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'payload' => null,
                 ]);
    }
}

