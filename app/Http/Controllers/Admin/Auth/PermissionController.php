<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Auth\StorePermissionRequest;
use App\Http\Requests\Auth\UpdatePermissionRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PermissionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $this->authorize('viewAny', Permission::class);

        if ($request->ajax()) {
            $roles = Permission::query()
                ->select(
                    'permissions.*',
                    'modules.name as module_name'
                )
                ->leftJoin('modules', 'modules.id', 'permissions.module_id');
            return DataTables::of($roles)
                ->addColumn('action', function ($row) {
                    $editUrl = route('admin.auth.permissions.edit', $row->id);
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

        return view('theme.adminlte.auth.permissions.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Permission::class);

        $data['modules']    = Module::get();
        $response['view']   = view('theme.adminlte.auth.permissions.create', $data)->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        $this->authorize('create', Permission::class);

        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');

        Permission::create($data);

        return response()->json([
            'message' => 'Permission created!',
            'redirect' => route('admin.auth.permissions.index')
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
        $permission = Permission::findOrFail($id);

        $this->authorize('update', $permission);

        $data['modules']    = Module::get();
        $data['permission'] = $permission;

        $response['view'] =  view('theme.adminlte.auth.permissions.edit', $data)->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $this->authorize('update', $permission);

        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');

        $permission->update($data);

        return response()->json([
            'message' => 'Permission updated!',
            'redirect' => route('admin.auth.permissions.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);

        $this->authorize('delete', $permission);

        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted!',
            'redirect' => route('admin.auth.permissions.index')
        ]);
    }
}
