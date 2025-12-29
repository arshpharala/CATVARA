<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Repositories\AdminRoleRepository;
use App\Http\Requests\Auth\StoreRoleRequest;
use App\Http\Requests\Auth\UpdateRoleRequest;
use App\Repositories\AdminPermissionRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        if ($request->ajax()) {
            $roles = Role::query();
            return DataTables::of($roles)
                ->addColumn('action', function ($row) {
                    $editUrl = route('admin.auth.roles.edit', $row->id);
                    // $deleteUrl = route('admin.auth.admins.destroy', $row->id);
                    // $restoreUrl = route('admin.auth.admins.restore', $row->id);
                    $editSidebar = true;
                    return view('theme.adminlte.components._table-actions', compact('editUrl', 'row', 'editSidebar'))->render();
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at?->format('d-M-Y  h:m A');
                })
                ->addColumn('is_active', fn($row) => !$row->is_active ? '<span class="badge badge-danger">Inactive</span>' : '<span class="badge badge-success">Active</span>')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('theme.adminlte.auth.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Role::class);

        $response['view'] =  view('theme.adminlte.auth.roles.create')->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {

        $this->authorize('create', Role::class);

        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');

        Role::create($data);

        return response()->json([
            'message' => 'Role created!',
            'redirect' => route('admin.auth.roles.index')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        $this->authorize('update', $role);

        $permissions = Permission::with('module')->get()->map(function ($permission) use ($role) {
            $permission->checked = $role->permissions->contains($permission->id);

            return $permission;
        });


        $data['permissions'] = $permissions;
        $data['role'] = $role;

        $response['view'] =  view('theme.adminlte.auth.roles.edit', $data)->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, string $id)
    {
        $role = Role::findOrFail($id);

        $this->authorize('update', $role);


        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');

        $role->update($data);
        $role->permissions()->sync($data['permissions'] ?? null);

        foreach ($role->admins as $admin) {
            app(AdminPermissionRepository::class)->clearForAdmin($admin);
            app(AdminRoleRepository::class)->clearForAdmin($admin);
        }

        return response()->json([
            'message' => 'Role updated!',
            'redirect' => route('admin.auth.roles.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        $this->authorize('delete', $role);

        $role->delete();

        return response()->json([
            'message' => 'Role deleted!',
            'redirect' => route('admin.auth.roles.index')
        ]);
    }
}
