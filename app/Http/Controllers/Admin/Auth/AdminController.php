<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Role;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Repositories\AdminRoleRepository;
use App\Http\Requests\Auth\StoreAdminRequest;
use App\Http\Requests\Auth\UpdateAdminRequest;
use App\Repositories\AdminPermissionRepository;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', new Admin());

        if ($request->ajax()) {
            $admins = Admin::query();
            return DataTables::of($admins)
                ->addColumn('action', function ($row) {
                    $editUrl = route('admin.auth.admins.edit', $row->id);
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

        return view('theme.adminlte.auth.admin.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Admin::class);

        $response['view'] =  view('theme.adminlte.auth.admin.create')->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminRequest $request)
    {
        $this->authorize('create', Admin::class);

        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');
        $data['password'] = bcrypt($request['password']);

        Admin::create($data);

        return response()->json([
            'message' => 'User created!',
            'redirect' => route('admin.auth.admins.index')
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
        $admin = Admin::with('roles')->findOrFail($id);

        $this->authorize('update', $admin);

        $roles = Role::get()->map(function ($role) use ($admin) {
            $role->checked = $admin->roles->contains($role->id);

            return $role;
        });

        $data['roles'] = $roles;
        $data['admin'] = $admin;

        $response['view'] =  view('theme.adminlte.auth.admin.edit', $data)->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminRequest $request, string $id)
    {
        $admin = Admin::findOrFail($id);

        $this->authorize('update', $admin);

        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request['password']);
        } else {
            unset($data['password']);
        }
        $admin->update($data);
        $admin->roles()->sync($data['roles'] ?? null);

        app(AdminPermissionRepository::class)->clearForAdmin($admin);
        app(AdminRoleRepository::class)->clearForAdmin($admin);

        return response()->json([
            'message' => 'User updated!',
            'redirect' => route('admin.auth.admins.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
