<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuruAuthThrottleTest extends TestCase
{
    public function test_guru_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/guru/auth/login', [])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/guru/auth/login', [])
            ->assertTooManyRequests();
    }
}
