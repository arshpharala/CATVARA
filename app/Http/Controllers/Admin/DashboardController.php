<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('theme.adminlte.dashboard');
    }

    public function redirectToCompanyDashboard()
    {
        // Uses helper active_company() you already asked me to implement
        $company = active_company();

        if (!$company) {
            return redirect()->route('company.select');
        }

        return redirect()->route('company.dashboard', ['company' => $company->uuid]);
    }
}
