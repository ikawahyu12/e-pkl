<?php

namespace Tests\Unit;

use App\Models\Guru;
use App\Models\Role;
use App\Models\User;
use App\Services\GuruAuthService;
use PHPUnit\Framework\TestCase;

class GuruAuthServiceTest extends TestCase
{
    public function test_active_guru_with_profile_is_eligible(): void
    {
        $this->assertTrue((new GuruAuthService)->isEligibleGuru($this->user('guru', 'aktif', true)));
    }

    public function test_non_guru_role_is_not_eligible(): void
    {
        $this->assertFalse((new GuruAuthService)->isEligibleGuru($this->user('siswa', 'aktif', true)));
    }

    public function test_inactive_guru_is_not_eligible(): void
    {
        $this->assertFalse((new GuruAuthService)->isEligibleGuru($this->user('guru', 'nonaktif', true)));
    }

    public function test_guru_without_profile_is_not_eligible(): void
    {
        $this->assertFalse((new GuruAuthService)->isEligibleGuru($this->user('guru', 'aktif', false)));
    }

    private function user(string $roleName, string $status, bool $hasGuruProfile): User
    {
        $user = new User;
        $user->status = $status;
        $user->setRelation('role', new Role(['nama' => $roleName]));
        $user->setRelation('guru', $hasGuruProfile ? new Guru : null);

        return $user;
    }
}
