<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UserStoreRequest;
use App\Http\Requests\Admin\Settings\UserUpdateRequest;
use App\Http\Requests\Admin\Settings\UserAssignCompanyRequest;
use App\Models\User;
use App\Models\Company\Company;
use App\Models\Auth\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::query()
                ->select('users.*') // Select all to ensure we have IDs for routes
                ->withCount('companies');

            // Filter: Status
            if ($request->filled('is_active')) {
                $query->where('is_active', (int) $request->is_active);
            }

            // Filter: User Type
            if ($request->filled('user_type')) {
                $query->where('user_type', $request->user_type);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('photo', function ($row) {
                    $src = $row->profile_photo
                        ? asset('storage/' . $row->profile_photo)
                        : asset('theme/adminlte/dist/img/user2-160x160.jpg');

                    return '<img src="' . e($src) . '" class="img-circle elevation-2 border" style="width:40px;height:40px;object-fit:cover;">';
                })

                ->editColumn('user_type', function ($row) {
                    $badge = ($row->user_type === 'SUPER_ADMIN') ? 'badge-dark' : 'badge-secondary';
                    return '<span class="badge ' . $badge . ' text-uppercase px-2 py-1" style="letter-spacing:.4px;">' . e($row->user_type) . '</span>';
                })

                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Active</span>'
                        : '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times mr-1"></i>Inactive</span>';
                })

                ->editColumn('last_login_at', function ($row) {
                    if (!$row->last_login_at) {
                        return '<span class="text-muted">—</span>';
                    }
                    return '<span class="small text-muted font-weight-bold">' . e(\Carbon\Carbon::parse($row->last_login_at)->format('d M, Y h:i A')) . '</span>';
                })

                ->addColumn('action', function ($row) {
                    $view = route('users.show', $row->id);
                    $edit = route('users.edit', $row->id);

                    return '
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                <i class="fas fa-cog"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right shadow-sm">
                <a class="dropdown-item" href="' . $view . '"><i class="fas fa-eye mr-2 text-primary"></i>View Profile</a>
                <a class="dropdown-item" href="' . $edit . '"><i class="fas fa-edit mr-2 text-info"></i>Edit</a>
            </div>
        </div>';
                })

                ->rawColumns(['photo', 'user_type', 'is_active', 'last_login_at', 'action'])
                ->make(true);
        }
        return view('theme.adminlte.settings.users.index');
    }

    public function create()
    {
        return view('theme.adminlte.settings.users.create');
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $photoPath = null;
            if ($request->hasFile('profile_photo')) {
                $photoPath = $request->file('profile_photo')->store('users', 'public');
            }

            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'user_type' => $data['user_type'] ?? 'ADMIN',
                'profile_photo' => $photoPath,
                'email_verified_at' => $data['email_verified_at'] ?? null,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message'  => __('crud.created', ['name' => 'User']),
                    'redirect' => route('users.index'),
                ]);
            }

            return redirect()->route('users.index')->with('success', __('crud.created', ['name' => 'User']));
        } catch (\Throwable $e) {
            DB::rollBack();

            if (!empty($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            throw $e;
        }
    }

    public function show(string $id)
    {
        $user = User::query()
            ->with(['companies' => function ($q) {
                $q->select('companies.id', 'companies.uuid', 'companies.name', 'companies.code')
                    ->withPivot(['is_owner', 'is_active'])
                    ->withTimestamps();
            }])
            ->findOrFail($id);

        // all companies for assignment dropdown
        $companies = Company::query()->select('id', 'uuid', 'name', 'code')->orderBy('name')->get();

        return view('theme.adminlte.settings.users.show', compact('user', 'companies'));
    }

    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        return view('theme.adminlte.settings.users.edit', compact('user'));
    }

    public function update(UserUpdateRequest $request, string $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $photoPath = $user->profile_photo;

            if ($request->hasFile('profile_photo')) {
                $newPath = $request->file('profile_photo')->store('users', 'public');

                if (!empty($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $photoPath = $newPath;
            }

            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'user_type' => $data['user_type'] ?? $user->user_type,
                'profile_photo' => $photoPath,
            ];

            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->update($payload);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message'  => __('crud.updated', ['name' => 'User']),
                    'redirect' => route('users.index'),
                ]);
            }

            return redirect()->route('users.index')->with('success', __('crud.updated', ['name' => 'User']));
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            throw $e;
        }
    }

    /**
     * Ajax: roles list by company_id (for assignment form)
     */
    public function rolesByCompany(Request $request)
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $roles = Role::query()
            ->select('id', 'name')
            ->where('company_id', $request->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    /**
     * Assign user to company + role (single role per company for now)
     */
    public function assignCompany(UserAssignCompanyRequest $request, User $user)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // Sync the main pivot (is_owner and is_active)
            $user->companies()->syncWithoutDetaching([
                $data['company_id'] => [
                    'is_owner' => $request->has('is_owner'), // Uses checkbox presence
                    'is_active' => $request->has('is_active'),
                    'updated_at' => now(),
                ]
            ]);

            // Sync the role (Company User Role)
            DB::table('company_user_role')->updateOrInsert(
                ['company_id' => $data['company_id'], 'user_id' => $user->id],
                ['role_id' => $data['role_id'], 'updated_at' => now(), 'created_at' => now()]
            );

            DB::commit();

            $user->forgetCompanyPermissionsCache((int) $data['company_id']);

            $msg = "Access permissions for {$user->name} updated successfully.";
            return $request->ajax()
                ? response()->json(['message' => $msg, 'redirect' => route('users.show', $user->id)])
                : redirect()->route('users.show', $user->id)->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $request->ajax()
                ? response()->json(['message' => 'Failed to update access: ' . $e->getMessage()], 500)
                : back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    public function removeCompany(Request $request, User $user)
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        DB::beginTransaction();
        try {
            DB::table('company_user_role')
                ->where('company_id', $request->company_id)
                ->where('user_id', $user->id)
                ->delete();

            DB::table('company_user')
                ->where('company_id', $request->company_id)
                ->where('user_id', $user->id)
                ->delete();

            DB::commit();

            $user->forgetCompanyPermissionsCache((int) $request->company_id);


            if ($request->ajax()) {
                return response()->json([
                    'message'  => __('crud.updated', ['name' => 'User Company Access']),
                    'redirect' => route('users.show', $user->id),
                ]);
            }

            return redirect()->route('users.show', $user->id)->with('success', __('crud.updated', ['name' => 'User Company Access']));
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            throw $e;
        }
    }
}
