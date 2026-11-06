<?php

namespace Tests\Feature;

use App\Exceptions\TokenStorageUnavailable;
use App\Services\SiswaAuthService;
use Mockery;
use Tests\TestCase;

class SiswaAuthTest extends TestCase
{
    public function test_login_requires_login_and_password(): void
    {
        $this->postJson('/api/v1/siswa/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['login', 'password']);
    }

    public function test_invalid_credentials_receive_a_generic_response(): void
    {
        $service = Mockery::mock(SiswaAuthService::class);
        $service->shouldReceive('login')
            ->once()
            ->with('unknown-login', 'invalid-password')
            ->andReturnNull();
        $this->app->instance(SiswaAuthService::class, $service);

        $this->postJson('/api/v1/siswa/auth/login', [
            'login' => 'unknown-login',
            'password' => 'invalid-password',
        ])
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Login atau password tidak valid.',
            ]);
    }

    public function test_missing_token_storage_returns_service_unavailable(): void
    {
        $service = Mockery::mock(SiswaAuthService::class);
        $service->shouldReceive('login')
            ->once()
            ->andThrow(new TokenStorageUnavailable);
        $this->app->instance(SiswaAuthService::class, $service);

        $this->postJson('/api/v1/siswa/auth/login', [
            'login' => 'siswa',
            'password' => 'valid-password',
        ])
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/siswa/auth/me')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/siswa/auth/logout')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ]);
    }

    public function test_siswa_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/siswa/auth/login', [])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/siswa/auth/login', [])
            ->assertTooManyRequests();
    }
}
