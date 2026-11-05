<?php

namespace Tests\Feature;

use App\Services\GuruAuthService;
use Mockery;
use Tests\TestCase;

class GuruAuthTest extends TestCase
{
    public function test_login_requires_login_and_password(): void
    {
        $this->postJson('/api/v1/guru/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['login', 'password']);
    }

    public function test_invalid_credentials_receive_a_generic_response(): void
    {
        $service = Mockery::mock(GuruAuthService::class);
        $service->shouldReceive('login')
            ->once()
            ->with('unknown-login', 'invalid-password')
            ->andReturnNull();
        $this->app->instance(GuruAuthService::class, $service);

        $this->postJson('/api/v1/guru/auth/login', [
            'login' => 'unknown-login',
            'password' => 'invalid-password',
        ])
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Login atau password tidak valid.',
            ]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/guru/auth/me')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/guru/auth/logout')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ]);
    }
}
