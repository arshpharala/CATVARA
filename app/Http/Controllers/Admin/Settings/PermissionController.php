<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\PermissionStoreRequest;
use App\Http\Requests\Admin\Settings\PermissionUpdateRequest;
use App\Models\Auth\Module;
use App\Models\Auth\Permission;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Permission::query()
                ->select([
                    'permissions.id',
                    'permissions.name',
                    'permissions.slug',
                    'permissions.module_id',
                    'permissions.is_active',
                    'permissions.created_at',
                    'modules.name as module_name',
                    'modules.slug as module_slug',
                ])
                ->leftJoin('modules', 'modules.id', '=', 'permissions.module_id');

            if ($request->filled('module_id')) {
                $query->where('permissions.module_id', $request->module_id);
            }

            if ($request->filled('is_active')) {
                $query->where('permissions.is_active', (int) $request->is_active);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn(
                    'name',
                    fn($row) =>
                    '<div class="font-weight-600 text-dark">' . e($row->name) . '</div>'
                )

                ->editColumn(
                    'slug',
                    fn($row) =>
                    $row->slug
                        ? '<span class="badge badge-light border">' . e($row->slug) . '</span>'
                        : '<span class="text-muted">—</span>'
                )

                ->addColumn('module', function ($row) {
                    $label = $row->module_name ?? '—';
                    $slug  = $row->module_slug ? '<div class="text-muted small">' . e($row->module_slug) . '</div>' : '';
                    return '<div class="text-dark font-weight-600">' . e($label) . '</div>' . $slug;
                })

                ->editColumn(
                    'is_active',
                    fn($row) =>
                    (int)$row->is_active === 1
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-secondary">Inactive</span>'
                )

                ->editColumn(
                    'created_at',
                    fn($row) =>
                    $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-M-Y h:i A') : '—'
                )

                ->addColumn('action', function ($row) {
                    $editUrl = route('permissions.edit', $row->id);

                    return '
                    <div class="dropdown">
                        <button class="btn btn-default btn-sm border dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow border-0">
                            <a class="dropdown-item py-2" href="' . $editUrl . '">
                                <i class="fas fa-edit mr-2 text-primary"></i> Edit Permission
                            </a>
                        </div>
                    </div>';
                })

                ->rawColumns(['name', 'slug', 'module', 'is_active', 'action'])
                ->make(true);
        }

        $modules = Module::query()->orderBy('name')->get();
        return view('theme.adminlte.settings.permissions.index', compact('modules'));
    }

    public function create()
    {
        $modules = Module::query()->orderBy('name')->get();
        return view('theme.adminlte.settings.permissions.create', compact('modules'));
    }

    public function store(PermissionStoreRequest $request)
    {
        $data = $request->validated();

        Permission::create([
            'name'      => $data['name'],
            'slug'      => $data['slug'],
            'module_id' => $data['module_id'],
            'is_active' => (bool)($data['is_active'] ?? true),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'message'  => __('crud.created', ['name' => 'Permission']),
                'redirect' => route('permissions.index'),
            ]);
        }

        return redirect()->route('permissions.index')->with('success', __('crud.created', ['name' => 'Permission']));
    }

    public function edit(string $id)
    {
        $permission = Permission::findOrFail($id);
        $modules = Module::query()->orderBy('name')->get();

        return view('theme.adminlte.settings.permissions.edit', compact('permission', 'modules'));
    }

    public function update(PermissionUpdateRequest $request, string $id)
    {
        $permission = Permission::findOrFail($id);
        $data = $request->validated();

        $permission->update([
            'name'      => $data['name'],
            'slug'      => $data['slug'],
            'module_id' => $data['module_id'],
            'is_active' => (bool)($data['is_active'] ?? false),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'message'  => __('crud.updated', ['name' => 'Permission']),
                'redirect' => route('permissions.index'),
            ]);
        }

        return redirect()->route('permissions.index')->with('success', __('crud.updated', ['name' => 'Permission']));
    }
}
