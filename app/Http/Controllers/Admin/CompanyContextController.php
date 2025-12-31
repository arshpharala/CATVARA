<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use Illuminate\Http\Request;

class CompanyContextController extends Controller
{
    /**
     * GET /dashboard
     * Decides where to send the user:
     * - If session has a valid company => redirect to /{company}/dashboard
     * - If user has exactly one company => auto-select + redirect
     * - Else => redirect to select-company
     */
    public function dashboardEntry(Request $request)
    {
        $user = $request->user();

        // 1) If session company exists and user has access -> redirect to company dashboard
        $sessionCompanyId = session('current_company_id');
        if ($sessionCompanyId) {
            $company = Company::query()->select('id', 'uuid')->find($sessionCompanyId);

            if ($company && $user->companies()->where('companies.id', $company->id)->exists()) {
                return redirect()->route('company.dashboard', ['company' => $company->uuid]);
            }

            // invalid selection
            session()->forget('current_company_id');
        }

        // 2) If only one company -> auto select
        $companies = $user->companies()->select('companies.id', 'companies.uuid')->get();

        if ($companies->count() === 1) {
            $only = $companies->first();
            session(['current_company_id' => $only->id]);

            return redirect()->route('company.dashboard', ['company' => $only->uuid]);
        }

        // 3) Select company screen
        return redirect()->route('company.select');
    }

    /**
     * GET /select-company
     */
    public function select(Request $request)
    {
        $companies = $request->user()
            ->companies()
            ->with('status:id,name,code')
            ->select('companies.id', 'companies.uuid', 'companies.name', 'companies.legal_name', 'companies.code', 'companies.logo', 'companies.website_url', 'companies.company_status_id')
            ->orderBy('companies.name')
            ->get();

        return view('theme.adminlte.company.select', compact('companies'));
    }

    /**
     * POST /select-company
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_uuid' => ['required', 'uuid'],
        ]);

        $user = $request->user();

        $company = Company::where('uuid', $request->company_uuid)->firstOrFail();

        // Ensure user has access
        if (!$user->companies()->where('companies.id', $company->id)->exists()) {
            return back()->with('error', 'You do not have access to this company.');
        }

        session(['current_company_id' => $company->id]);

        return redirect()->route('company.dashboard', ['company' => $company->uuid]);
    }

    /**
     * POST /switch-company/reset
     */
    public function reset()
    {
        session()->forget('current_company_id');

        return redirect()->route('company.select')
            ->with('success', 'Please select a company.');
    }
}
