<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanySelected
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // user must have at least 1 company
        $companiesCount = $user->companies()->count();
        if ($companiesCount === 0) {
            abort(403, 'No company assigned to this user.');
        }

        // if session has current company, ok
        if (session()->has('current_company_id')) {
            return $next($request);
        }

        // auto-select if only one
        if ($companiesCount === 1) {
            $company = $user->companies()->first();
            session(['current_company_id' => $company->id]);

            return $next($request);
        }

        // else require selection
        return redirect()->route('company.select');
    }
}
