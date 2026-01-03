<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerStoreRequest;
use App\Http\Requests\Admin\CustomerUpdateRequest;
use App\Models\Customer\Customer;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Company $company)
    {
        if ($request->ajax()) {

            $query = Customer::query()
                ->select(
                    'customers.id',
                    'customers.uuid',
                    'customers.display_name',
                    'customers.type',
                    'customers.email',
                    'customers.phone',
                    'customers.legal_name',
                    'customers.is_active',
                    'customers.created_at'
                )
                ->where('customers.company_id', $company->id);

            // Filters
            if ($request->filled('type')) {
                $query->where('customers.type', $request->type);
            }

            if ($request->filled('is_active')) {
                $query->where('customers.is_active', $request->is_active);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('display_name', function ($row) {
                    $name = e($row->display_name);
                    if ($row->legal_name) {
                        $name .= '<br><small class="text-muted">' . e($row->legal_name) . '</small>';
                    }
                    return $name;
                })

                ->addColumn('type_badge', function ($row) {
                    $badge = $row->type === 'COMPANY' ? 'primary' : 'secondary';
                    $icon = $row->type === 'COMPANY' ? 'building' : 'user';
                    return '<span class="badge badge-' . $badge . '"><i class="fas fa-' . $icon . ' mr-1"></i>' . e($row->type) . '</span>';
                })

                ->editColumn('email', function ($row) {
                    return $row->email
                        ? '<a href="mailto:' . e($row->email) . '">' . e($row->email) . '</a>'
                        : '<span class="text-muted">—</span>';
                })

                ->editColumn('phone', function ($row) {
                    return $row->phone
                        ? '<a href="tel:' . e($row->phone) . '">' . e($row->phone) . '</a>'
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('status_badge', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge badge-success">Active</span>';
                    }
                    return '<span class="badge badge-danger">Inactive</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? \Carbon\Carbon::parse($row->created_at)->format('d-M-Y h:i A')
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('action', function ($row) use ($company) {
                    $compact['showUrl'] = route('customers.show', [$company->uuid, $row->id]);
                    $compact['editUrl'] = route('customers.edit', [$company->uuid, $row->id]);
                    $compact['deleteUrl'] = null;
                    $compact['editSidebar'] = false;

                    return view('theme.adminlte.components._table-actions', $compact)->render();
                })

                ->rawColumns([
                    'display_name',
                    'type_badge',
                    'email',
                    'phone',
                    'status_badge',
                    'created_at',
                    'action'
                ])
                ->make(true);
        }

        return view('theme.adminlte.customers.index', compact('company'));
    }

    /**
     * Stats API for dashboard cards
     */
    public function stats(Request $request, Company $company)
    {
        $baseQuery = Customer::where('company_id', $company->id);

        $all = (clone $baseQuery)->count();
        $active = (clone $baseQuery)->where('is_active', true)->count();
        $inactive = (clone $baseQuery)->where('is_active', false)->count();
        $companies = (clone $baseQuery)->where('type', 'COMPANY')->count();
        $individuals = (clone $baseQuery)->where('type', 'INDIVIDUAL')->count();

        return response()->json([
            'all_customers' => $all,
            'active_customers' => $active,
            'inactive_customers' => $inactive,
            'company_customers' => $companies,
            'individual_customers' => $individuals,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Company $company)
    {
        return view('theme.adminlte.customers.create', compact('company'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerStoreRequest $request, Company $company)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $customer = Customer::create([
                'uuid' => Str::uuid(),
                'company_id' => $company->id,
                'type' => $data['type'],
                'display_name' => $data['display_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'legal_name' => $data['legal_name'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Customer Created Successfully',
                    'redirect' => route('customers.index', $company->uuid),
                ]);
            }

            return redirect()
                ->route('customers.index', $company->uuid)
                ->with('success', 'Customer Created Successfully');

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company, string $id)
    {
        $customer = Customer::where('company_id', $company->id)
            ->with('addresses')
            ->findOrFail($id);

        return view('theme.adminlte.customers.show', compact('company', 'customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company, string $id)
    {
        $customer = Customer::where('company_id', $company->id)->findOrFail($id);

        return view('theme.adminlte.customers.edit', compact('company', 'customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerUpdateRequest $request, Company $company, string $id)
    {
        $customer = Customer::where('company_id', $company->id)->findOrFail($id);
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $customer->update([
                'type' => $data['type'],
                'display_name' => $data['display_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'legal_name' => $data['legal_name'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Customer Updated Successfully',
                    'redirect' => route('customers.index', $company->uuid),
                ]);
            }

            return redirect()
                ->route('customers.index', $company->uuid)
                ->with('success', 'Customer Updated Successfully');

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }
}
