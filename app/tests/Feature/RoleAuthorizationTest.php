<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_open_admin_or_tutor_routes_directly(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_STUDENT));

        $this->get('/admin/users')->assertForbidden();
        $this->get('/tutor/daily-plans')->assertForbidden();
    }

    public function test_tutor_cannot_open_admin_pages_or_manage_accounts(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_TUTOR));

        $this->get('/admin/users')->assertForbidden();
        $this->post('/admin/users', [])->assertForbidden();
    }

    public function test_each_role_can_open_its_own_workspace_routes(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_ADMIN));
        $this->get('/admin/users')->assertOk();

        auth()->logout();
        $this->actingAs($this->userWithRole(User::ROLE_TUTOR));
        $this->get('/tutor/daily-plans')->assertOk();

        auth()->logout();
        $this->actingAs($this->userWithRole(User::ROLE_STUDENT));
        $this->get('/student/materials')->assertOk();
    }

    public function test_inactive_accounts_cannot_log_in_or_continue_a_session(): void
    {
        $user = $this->userWithRole(User::ROLE_STUDENT);
        $user->forceFill(['status' => User::STATUS_INACTIVE])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');
        $this->assertGuest();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
