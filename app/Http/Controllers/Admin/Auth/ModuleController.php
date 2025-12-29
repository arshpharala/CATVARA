<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Module;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ModuleRepository;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Auth\StoreModuleRequest;
use App\Http\Requests\Auth\UpdateModuleRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ModuleController extends Controller
{
    use AuthorizesRequests;

    protected ModuleRepository $modules;

    public function __construct(ModuleRepository $modules)
    {
        $this->modules = $modules;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Module::class);

        if ($request->ajax()) {
            $modules = $this->modules->model()::query(); // use underlying model
            return DataTables::of($modules)
                ->addColumn('action', function ($row) {
                    $editUrl = route('admin.auth.modules.edit', $row->id);
                    $editSidebar = true;
                    return view('theme.adminlte.components._table-actions', compact('editUrl', 'row', 'editSidebar'))->render();
                })
                ->editColumn('created_at', fn($row) => $row->created_at?->format('d-M-Y  h:m A'))
                ->addColumn('is_active', fn($row) => !$row->is_active
                    ? '<span class="badge badge-danger">Inactive</span>'
                    : '<span class="badge badge-success">Active</span>')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('theme.adminlte.auth.modules.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Module::class);

        $response['view'] =  view('theme.adminlte.auth.modules.create')->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreModuleRequest $request)
    {
        $this->authorize('create', Module::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->modules->create($data);

        return response()->json([
            'message' => 'Module created!',
            'redirect' => route('admin.auth.modules.index')
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
        $module = $this->modules->find($id);

        $this->authorize('update', $module);

        $data['module'] = $module;

        $response['view'] = view('theme.adminlte.auth.modules.edit', $data)->render();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateModuleRequest $request, string $id)
    {
        $module = $this->modules->find($id);

        $this->authorize('update', $module);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->modules->update($data, $id);

        return response()->json([
            'message' => 'Module updated!',
            'redirect' => route('admin.auth.modules.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $module = $this->modules->find($id);

        $this->authorize('delete', $module);

        return response()->json([
            'message' => 'Module deleted!',
            'redirect' => route('admin.auth.modules.index')
        ]);
    }
}
