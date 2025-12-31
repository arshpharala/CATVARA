<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\RoleStoreRequest;
use App\Http\Requests\Admin\Settings\RoleUpdateRequest;
use App\Models\Auth\Module;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function index(Company $company, Request $request)
    {
        if ($request->ajax()) {

            $query = Role::query()
                ->where('company_id', $company->id)
                ->select(['id', 'company_id', 'name', 'slug', 'is_active', 'created_at'])
                ->withCount('permissions');

            if ($request->filled('is_active')) {
                $query->where('is_active', (int) $request->is_active);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('name', fn($row) => '<div class="font-weight-600 text-dark">' . e($row->name) . '</div>')

                ->editColumn(
                    'slug',
                    fn($row) => $row->slug
                        ? '<span class="badge badge-light border">' . e($row->slug) . '</span>'
                        : '<span class="text-muted">—</span>'
                )

                ->editColumn(
                    'permissions_count',
                    fn($row) =>
                    '<span class="badge badge-info"><i class="fas fa-key mr-1"></i>' . (int)$row->permissions_count . '</span>'
                )

                ->editColumn(
                    'is_active',
                    fn($row) =>
                    $row->is_active
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-secondary">Inactive</span>'
                )

                ->editColumn(
                    'created_at',
                    fn($row) =>
                    $row->created_at ? $row->created_at->format('d-M-Y h:i A') : '—'
                )

                ->addColumn('action', function ($row) use ($company) {
                    $editUrl = route('company.settings.roles.edit', ['company' => $company->uuid, 'role' => $row->id]);

                    return '
                    <div class="dropdown">
                        <button class="btn btn-default btn-sm border dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow border-0">
                            <a class="dropdown-item py-2" href="' . $editUrl . '">
                                <i class="fas fa-edit mr-2 text-primary"></i> Edit Role
                            </a>
                        </div>
                    </div>';
                })

                ->rawColumns(['name', 'slug', 'permissions_count', 'is_active', 'action'])
                ->make(true);
        }

        return view('theme.adminlte.settings.roles.index', compact('company'));
    }

    public function create(Company $company)
    {
        $modules = Module::query()
            ->where('is_active', true)
            ->with(['permissions' => function ($q) {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return view('theme.adminlte.settings.roles.create', compact('company', 'modules'));
    }

    public function store(Company $company, RoleStoreRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $slug = $data['slug'] ?? Str::slug($data['name']);

            // Enforce uniqueness per company (roles table already has unique(company_id, slug))
            $role = Role::create([
                'company_id' => $company->id,
                'name'       => $data['name'],
                'slug'       => $slug,
                'is_active'  => (bool)($data['is_active'] ?? true),
            ]);

            $permissionIds = $data['permissions'] ?? [];
            $role->permissions()->sync($permissionIds);

            DB::commit();

            $redirect = route('company.settings.roles.index', ['company' => $company->uuid]);

            if ($request->ajax()) {
                return response()->json([
                    'message'  => __('crud.created', ['name' => 'Role']),
                    'redirect' => $redirect,
                ]);
            }

            return redirect($redirect)->with('success', __('crud.created', ['name' => 'Role']));
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            throw $e;
        }
    }

    public function edit(Company $company, Role $role)
    {
        // Safety: role must belong to company
        abort_unless($role->company_id === $company->id, 404);

        $modules = Module::query()
            ->where('is_active', true)
            ->with(['permissions' => function ($q) {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $selected = $role->permissions()->pluck('permissions.id')->toArray();

        return view('theme.adminlte.settings.roles.edit', compact('company', 'role', 'modules', 'selected'));
    }

    public function update(Company $company, RoleUpdateRequest $request, Role $role)
    {
        abort_unless($role->company_id === $company->id, 404);

        $data = $request->validated();

        DB::beginTransaction();
        try {
            $slug = $data['slug'] ?? Str::slug($data['name']);

            $role->update([
                'name'      => $data['name'],
                'slug'      => $slug,
                'is_active' => (bool)($data['is_active'] ?? false),
            ]);

            $permissionIds = $data['permissions'] ?? [];
            $role->permissions()->sync($permissionIds);

            DB::commit();

            $redirect = route('company.settings.roles.index', ['company' => $company->uuid]);

            if ($request->ajax()) {
                return response()->json([
                    'message'  => __('crud.updated', ['name' => 'Role']),
                    'redirect' => $redirect,
                ]);
            }

            return redirect($redirect)->with('success', __('crud.updated', ['name' => 'Role']));
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            throw $e;
        }
    }
}
