<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleId = Role::query()->where('nama', 'siswa')->value('id');

        if ($roleId === null) {
            throw new RuntimeException(
                'Seed role siswa before creating a User factory instance.'
            );
        }

        return [
            'role_id' => $roleId,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make(Str::random(40)),
            'nama_tampilan' => fake()->name(),
            'status' => 'aktif',
            'wajib_ganti_password' => true,
        ];
    }
}