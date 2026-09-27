<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocalAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_tutor_and_student_accounts(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_ADMIN));

        foreach (User::MANAGEABLE_ROLES as $role) {
            $email = $role.'@example.com';
            $this->post('/admin/users', [
                'name' => ucfirst($role),
                'email' => $email,
                'role' => $role,
                'password' => 'S3cure Test Passphrase!',
                'password_confirmation' => 'S3cure Test Passphrase!',
            ])->assertRedirect(route('admin.users.index', absolute: false));

            $created = User::query()->where('email', $email)->firstOrFail();
            $this->assertSame($role, $created->role);
            $this->assertSame(User::STATUS_ACTIVE, $created->status);
            $this->assertNotNull($created->email_verified_at);
        }
    }

    public function test_admin_user_creation_rejects_duplicate_email_privileged_role_and_password_mismatch(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        $this->actingAs($admin);

        $this->post('/admin/users', [
            'name' => 'Duplicate',
            'email' => $existing->email,
            'role' => User::ROLE_STUDENT,
            'password' => 'S3cure Test Passphrase!',
            'password_confirmation' => 'S3cure Test Passphrase!',
        ])->assertSessionHasErrors('email');

        $this->post('/admin/users', [
            'name' => 'Admin Attempt',
            'email' => 'promoted@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => 'S3cure Test Passphrase!',
            'password_confirmation' => 'S3cure Test Passphrase!',
        ])->assertSessionHasErrors('role');

        $this->post('/admin/users', [
            'name' => 'Mismatch',
            'email' => 'mismatch@example.com',
            'role' => User::ROLE_STUDENT,
            'password' => 'S3cure Test Passphrase!',
            'password_confirmation' => 'Different Passphrase!',
        ])->assertSessionHasErrors('password');
    }

    public function test_only_admin_can_change_status_or_reset_user_access(): void
    {
        $target = $this->userWithRole(User::ROLE_TUTOR);

        foreach ([User::ROLE_TUTOR, User::ROLE_STUDENT] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->patch('/admin/users/'.$target->id.'/status', ['status' => User::STATUS_INACTIVE])->assertForbidden();
            $this->put('/admin/users/'.$target->id.'/access', [])->assertForbidden();
        }
    }

    public function test_admin_can_change_status_and_reset_access_of_tutor_or_student(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $this->actingAs($admin);

        $this->patch('/admin/users/'.$student->id.'/status', ['status' => User::STATUS_INACTIVE])
            ->assertRedirect(route('admin.users.index', absolute: false));
        $this->assertSame(User::STATUS_INACTIVE, $student->fresh()->status);

        $this->put('/admin/users/'.$student->id.'/access', [
            'password' => 'New Access Passphrase 234!',
            'password_confirmation' => 'New Access Passphrase 234!',
        ])->assertRedirect(route('admin.users.index', absolute: false));
        $student->refresh();
        $this->assertTrue(Hash::check('New Access Passphrase 234!', $student->password));
        $this->assertSame(User::STATUS_INACTIVE, $student->status, 'Resetting a password must not re-enable a disabled account.');
    }

    public function test_admin_cannot_change_an_admin_account_or_its_own_access(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $otherAdmin = $this->userWithRole(User::ROLE_ADMIN);
        $this->actingAs($admin);

        $this->patch('/admin/users/'.$otherAdmin->id.'/status', ['status' => User::STATUS_INACTIVE])->assertForbidden();
        $this->put('/admin/users/'.$admin->id.'/access', [
            'password' => 'New Access Passphrase 234!',
            'password_confirmation' => 'New Access Passphrase 234!',
        ])->assertForbidden();
    }

    public function test_local_admin_seeder_uses_configured_credentials(): void
    {
        config()->set('ruang.local_admin', [
            'name' => 'Configured Administrator',
            'email' => 'configured-admin@example.com',
            'password' => 'Local Seed Passphrase 234!',
        ]);

        $this->seed(LocalAdminSeeder::class);

        $admin = User::query()->where('email', 'configured-admin@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame(User::STATUS_ACTIVE, $admin->status);
        $this->assertTrue(Hash::check('Local Seed Passphrase 234!', $admin->password));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
