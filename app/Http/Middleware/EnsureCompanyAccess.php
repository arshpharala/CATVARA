<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $company = $request->route('company'); // resolved by uuid binding

        if (!$user || !$company) {
            abort(403);
        }

        $hasAccess = $user->companies()
            ->where('companies.id', $company->id)
            ->exists();

        if (!$hasAccess) {
            return redirect()
                ->route('admin.company.select')
                ->with('error', 'You do not have access to the selected company.');
        }

        return $next($request);
    }
}
