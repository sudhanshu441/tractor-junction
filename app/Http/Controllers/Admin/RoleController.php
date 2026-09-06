<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        // Grouped by module so the matrix reads the way the spec documents it
        $permissions = Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $p) => explode('.', $p->name)[0]);

        return view('admin.roles.edit', [
            'role' => $role,
            'groups' => $permissions,
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 403, __('The super-admin role always holds every permission.'));

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        activity()->performedOn($role)->causedBy($request->user())
            ->withProperties(['permissions' => $data['permissions'] ?? []])
            ->log('Updated role permissions');

        return redirect()->route('admin.roles.index')
            ->with('success', __('Permissions updated for :role.', ['role' => $role->name]));
    }
}
