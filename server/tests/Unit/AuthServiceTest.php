<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth as AuthFacade;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure JWT config exists in test env
        Config::set('jwt.secret', 'test_secret_key_for_ci');
        Config::set('jwt.algo', 'HS256');
    }

    #[Test]
    public function it_registers_a_user()
    {
        // Mock token generation
        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->andReturn('fake_token_register');

        $request = new Request([
            'name' => 'John Doe',                // service expects 'name'
            'email' => 'john@example.com',
            'password' => 'password123',        // min:8
            // omit role -> defaults to 'Student'
        ]);

        $user = \App\Services\Common\AuthService::register($request);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name'  => 'John Doe',
            'role'  => 'Student',
        ]);

        $this->assertNotNull($user->token);
        $this->assertEquals('fake_token_register', $user->token);
    }

    #[Test]
    public function login_returns_user_with_correct_credentials()
    {
        $user = User::factory()->create([
            'name'     => 'John Doe',
            'email'    => 'john@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'Student',
        ]);

        // Mock JWT attempt and Auth::user()
        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andReturn('fake_token_login');

        AuthFacade::shouldReceive('user')
            ->andReturn($user);

        $request = new Request([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $loggedInUser = \App\Services\Common\AuthService::login($request);

        $this->assertNotNull($loggedInUser);
        $this->assertEquals($user->email, $loggedInUser->email);
        $this->assertEquals('fake_token_login', $loggedInUser->token);
    }

    #[Test]
    public function login_returns_null_with_wrong_credentials()
    {
        // attempt() returns false for bad creds
        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andReturn(false);

        $request = new Request([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $user = \App\Services\Common\AuthService::login($request);

        $this->assertNull($user);
    }
}
