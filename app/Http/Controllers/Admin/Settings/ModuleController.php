<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\ModuleStoreRequest;
use App\Http\Requests\Admin\Settings\ModuleUpdateRequest;
use App\Models\Auth\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Module::query()
                ->select(['id', 'name', 'slug', 'is_active', 'created_at']);

            if ($request->filled('is_active')) {
                $query->where('is_active', (int) $request->is_active);
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

                ->addColumn('action', function ($row) {
                    $editUrl = route('modules.edit', $row->id);

                    return '
                    <div class="dropdown">
                        <button class="btn btn-default btn-sm border dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow border-0">
                            <a class="dropdown-item py-2" href="' . $editUrl . '">
                                <i class="fas fa-edit mr-2 text-primary"></i> Edit Module
                            </a>
                        </div>
                    </div>';
                })

                ->rawColumns(['name', 'slug', 'is_active', 'action'])
                ->make(true);
        }

        return view('theme.adminlte.settings.modules.index');
    }

    public function create()
    {
        return view('theme.adminlte.settings.modules.create');
    }

    public function store(ModuleStoreRequest $request)
    {
        $data = $request->validated();

        $slug = $data['slug'] ?? Str::slug($data['name']);

        Module::create([
            'name'      => $data['name'],
            'slug'      => $slug,
            'is_active' => (bool)($data['is_active'] ?? true),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'message'  => __('crud.created', ['name' => 'Module']),
                'redirect' => route('modules.index'),
            ]);
        }

        return redirect()->route('modules.index')->with('success', __('crud.created', ['name' => 'Module']));
    }

    public function edit(string $id)
    {
        $module = Module::findOrFail($id);
        return view('theme.adminlte.settings.modules.edit', compact('module'));
    }

    public function update(ModuleUpdateRequest $request, string $id)
    {
        $module = Module::findOrFail($id);
        $data = $request->validated();

        $slug = $data['slug'] ?? Str::slug($data['name']);

        $module->update([
            'name'      => $data['name'],
            'slug'      => $slug,
            'is_active' => (bool)($data['is_active'] ?? false),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'message'  => __('crud.updated', ['name' => 'Module']),
                'redirect' => route('modules.index'),
            ]);
        }

        return redirect()->route('modules.index')->with('success', __('crud.updated', ['name' => 'Module']));
    }
}
