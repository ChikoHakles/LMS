<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LocalAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The local administrator seeder is only available in local and testing environments.');
        }

        $name = config('ruang.local_admin.name');
        $email = config('ruang.local_admin.email');
        $password = config('ruang.local_admin.password');

        if (! is_string($email) || trim($email) === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('Set RUANG_LOCAL_ADMIN_EMAIL and RUANG_LOCAL_ADMIN_PASSWORD before seeding the local administrator.');
        }

        $user = User::query()->firstOrNew(['email' => mb_strtolower($email)]);
        $user->fill(['name' => $name, 'password' => $password]);
        $user->email_verified_at = now();
        $user->forceFill([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ])->save();
    }
}
