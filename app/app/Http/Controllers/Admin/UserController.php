<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserAccessRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Users', [
            'users' => User::query()
                ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'roles' => User::MANAGEABLE_ROLES,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = new User;
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->email_verified_at = now();
        $user->forceFill([
            'role' => $data['role'],
            'status' => User::STATUS_ACTIVE,
        ])->save();

        return to_route('admin.users.index')->with('status', 'Akun berhasil dibuat.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        $this->ensureManageableTarget($request->user(), $user);

        $user->forceFill([
            'status' => $request->validated('status'),
        ])->save();

        return to_route('admin.users.index')->with('status', 'Status akun berhasil diperbarui.');
    }

    public function resetAccess(ResetUserAccessRequest $request, User $user): RedirectResponse
    {
        $this->ensureManageableTarget($request->user(), $user);

        $user->password = $request->validated('password');
        $user->save();

        return to_route('admin.users.index')->with('status', 'Akses akun berhasil diatur ulang.');
    }

    private function ensureManageableTarget(?User $actor, User $target): void
    {
        abort_unless(
            $actor?->isAdmin()
                && ! $actor->is($target)
                && $target->hasRole(...User::MANAGEABLE_ROLES),
            403,
        );
    }
}
