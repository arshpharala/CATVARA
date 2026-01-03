<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\CompanyStoreRequest;
use App\Http\Requests\Admin\Settings\CompanyUpdateRequest;
use App\Models\Company\Company;
use App\Models\Company\CompanyDetail;
use App\Models\Company\CompanyStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Company::query()
                ->select(
                    'companies.id',
                    'companies.name',
                    'companies.legal_name',
                    'companies.code',
                    'companies.website_url',
                    'companies.company_status_id',
                    'companies.created_at',
                    'companies.logo',
                    'company_statuses.name as company_status',
                    'company_statuses.code as company_status_code'
                )
                ->leftJoin('company_statuses', 'company_statuses.id', '=', 'companies.company_status_id')
                ->withCount('users');

            if ($request->filled('company_status_id')) {
                $query->where('companies.company_status_id', $request->company_status_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('logo', function ($row) {
                    $src = $row->logo
                        ? asset('storage/' . $row->logo)
                        : asset('theme/adminlte/dist/img/AdminLTELogo.png');

                    // requested: img-sm
                    return '<img src="' . e($src) . '" class="img-sm company-logo" alt="Logo">';
                })

                ->editColumn('name', function ($row) {
                    return e($row->name);
                })

                ->editColumn('legal_name', function ($row) {
                    return $row->legal_name ? e($row->legal_name) : '<span class="text-muted">—</span>';
                })

                ->editColumn('code', function ($row) {
                    return $row->code
                        ? '<span class="badge badge-light border">' . e($row->code) . '</span>'
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('company_status_badge', function ($row) {
                    $name = $row->company_status ?? 'N/A';
                    $code = strtoupper($row->company_status_code ?? '');

                    $badge = 'secondary';
                    if ($code === 'ACTIVE') $badge = 'success';
                    if ($code === 'SUSPENDED') $badge = 'warning';
                    if ($code === 'EXPIRED') $badge = 'danger';
                    if ($code === 'CLOSED') $badge = 'danger';

                    return '<span class="badge badge-' . $badge . '">' . e($name) . '</span>';
                })

                ->editColumn('website_url', function ($row) {
                    if (!$row->website_url) return '<span class="text-muted">—</span>';

                    $url = $row->website_url;
                    $label = parse_url($url, PHP_URL_HOST) ?: $url;

                    return '<a href="' . e($url) . '" target="_blank" class="text-primary">
                              <i class="fas fa-external-link-alt mr-1"></i>' . e($label) . '
                            </a>';
                })

                ->editColumn('users_count', function ($row) {
                    return '<span class="badge badge-info"><i class="fas fa-users mr-1"></i>' . (int) $row->users_count . '</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? \Carbon\Carbon::parse($row->created_at)->format('d-M-Y h:i A')
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('action', function ($row) {
                    $compact['editUrl'] = route('companies.edit', $row->id);
                    $compact['deleteUrl'] = null;
                    $compact['editSidebar'] = false;

                    return view('theme.adminlte.components._table-actions', $compact)->render();
                })

                ->rawColumns([
                    'logo',
                    'legal_name',
                    'code',
                    'company_status_badge',
                    'website_url',
                    'users_count',
                    'created_at',
                    'action'
                ])
                ->make(true);
        }

        $statuses = CompanyStatus::query()->orderBy('name')->get();
        return view('theme.adminlte.settings.companies.index', compact('statuses'));
    }

    /**
     * Stats API: Active, Suspended, Expired, All
     */
    public function stats(Request $request)
    {
        $activeId = CompanyStatus::where('code', 'ACTIVE')->value('id');
        $suspendedId = CompanyStatus::where('code', 'SUSPENDED')->value('id');

        // Expired can be EXPIRED; fallback to CLOSED if you use that as "expired"
        $expiredId = CompanyStatus::where('code', 'EXPIRED')->value('id')
            ?: CompanyStatus::where('code', 'CLOSED')->value('id');

        $all = Company::count();
        $active = $activeId ? Company::where('company_status_id', $activeId)->count() : 0;
        $suspended = $suspendedId ? Company::where('company_status_id', $suspendedId)->count() : 0;
        $expired = $expiredId ? Company::where('company_status_id', $expiredId)->count() : 0;

        return response()->json([
            'all_companies' => $all,
            'active_companies' => $active,
            'suspended_companies' => $suspended,
            'expired_companies' => $expired,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statuses = CompanyStatus::query()->orderBy('name')->get();

        $data['statuses'] = $statuses;

        return view('theme.adminlte.settings.companies.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CompanyStoreRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('companies', 'public');
            }

            $company = Company::create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'],
                'code' => Str::upper($data['code']),
                'website_url' => $data['website_url'] ?? null,
                'company_status_id' => $data['company_status_id'],
                'logo' => $logoPath,
            ]);

            CompanyDetail::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'invoice_prefix'  => $data['invoice_prefix'] ?? null,
                    'invoice_postfix' => $data['invoice_postfix'] ?? null,
                    'quote_prefix'    => $data['quote_prefix'] ?? null,
                    'quote_postfix'   => $data['quote_postfix'] ?? null,
                    'address'         => $data['address'] ?? null,
                    'tax_number'      => $data['tax_number'] ?? null,
                ]
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message'  => 'Company Created Successfully',
                    'redirect' => route('companies.index'),
                ]);
            }

            return redirect()
                ->route('companies.index')
                ->with('success', 'Company Created Successfully');
        } catch (\Throwable $e) {

            DB::rollBack();

            // cleanup uploaded logo on failure
            if (!empty($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }


            if ($request->ajax()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $company = Company::with(['detail', 'baseCurrency', 'paymentTerms'])->findOrFail($id);
        $statuses = CompanyStatus::query()->orderBy('name')->get();
        $currencies = \App\Models\Pricing\Currency::where('is_active', true)->get();
        $paymentTerms = \App\Models\Accounting\PaymentTerm::where('is_active', true)->get();

        return view('theme.adminlte.settings.companies.edit', compact('company', 'statuses', 'currencies', 'paymentTerms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyUpdateRequest $request, string $id)
    {
        $company = Company::with('detail')->findOrFail($id);
        $data = $request->validated();

        DB::beginTransaction();

        try {

            $logoPath = $company->logo;

            if ($request->hasFile('logo')) {
                $newPath = $request->file('logo')->store('companies', 'public');

                if (!empty($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }

                $logoPath = $newPath;
            }

            $company->update([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'],
                'code' => Str::upper($data['code']),
                'website_url' => $data['website_url'] ?? null,
                'company_status_id' => $data['company_status_id'],
                'logo' => $logoPath,
                // Only update base currency if it's currently NULL
                'base_currency_id' => is_null($company->base_currency_id) && isset($data['base_currency_id']) 
                                        ? $data['base_currency_id'] 
                                        : $company->base_currency_id,
            ]);

            CompanyDetail::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'invoice_prefix'  => $data['invoice_prefix'] ?? null,
                    'invoice_postfix' => $data['invoice_postfix'] ?? null,
                    'quote_prefix'    => $data['quote_prefix'] ?? null,
                    'quote_postfix'   => $data['quote_postfix'] ?? null,
                    'address'         => $data['address'] ?? null,
                    'tax_number'      => $data['tax_number'] ?? null,
                ]
            );

            // Sync Payment Terms
            if (isset($data['payment_terms'])) {
                // If we had a default term, we would pivot that here.
                // For now just basic sync.
                $company->paymentTerms()->sync($data['payment_terms']);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message'  => 'Company Updated Successfully',
                    'redirect' => route('companies.index'),
                ]);
            }

            return redirect()
                ->route('companies.index')
                ->with('success', 'Company Updated Successfully');
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
