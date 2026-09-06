<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', [
            'roles' => Role::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /** DataTables server-side source. */
    public function data(Request $request): JsonResponse
    {
        $query = User::staff()->with('roles');

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%"));
        }

        if ($roleId = $request->integer('role_id')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
        }

        $total = User::staff()->count();
        $filtered = (clone $query)->count();

        $columns = ['name', 'email', 'mobile', 'last_login_at', 'is_active'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $rows = $query->orderBy($orderCol, $orderDir)
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'mobile' => $u->mobile,
                'roles' => $u->roles->pluck('name')->implode(', '),
                'last_login_at' => $u->last_login_at?->diffForHumans() ?? '—',
                'is_active' => $u->is_active,
                'edit_url' => route('admin.staff.edit', $u),
                'toggle_url' => route('admin.staff.toggle', $u),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.form', [
            'user' => new User(['user_type' => 'staff', 'is_active' => true]),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            ...collect($data)->except(['role', 'password'])->all(),
            'user_type' => 'staff',
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);

        activity()->performedOn($user)->causedBy($request->user())
            ->log('Created staff user');

        return redirect()->route('admin.staff.index')
            ->with('success', __('Staff user :name created.', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        abort_unless($user->isStaff(), 404);

        return view('admin.staff.form', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(StaffRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();

        $user->update([
            ...collect($data)->except(['role', 'password'])->all(),
            ...(filled($data['password'] ?? null) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        $user->syncRoles([$data['role']]);

        activity()->performedOn($user)->causedBy($request->user())->log('Updated staff user');

        return redirect()->route('admin.staff.index')
            ->with('success', __('Staff user :name updated.', ['name' => $user->name]));
    }

    /** AJAX toggle from the list screen. */
    public function toggle(Request $request, User $user): JsonResponse
    {
        abort_unless($user->isStaff(), 404);
        abort_if($user->is($request->user()), 422, __('You cannot block your own account.'));

        $user->update([
            'is_active' => ! $user->is_active,
            'blocked_reason' => $user->is_active ? $request->string('reason')->toString() : null,
            'blocked_at' => $user->is_active ? now() : null,
        ]);

        activity()->performedOn($user)->causedBy($request->user())
            ->log($user->is_active ? 'Unblocked staff user' : 'Blocked staff user');

        return response()->json([
            'status' => 'ok',
            'message' => $user->is_active ? __('Account activated.') : __('Account blocked.'),
            'data' => ['is_active' => $user->is_active],
        ]);
    }
}
