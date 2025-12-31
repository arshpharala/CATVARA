<?php

use App\Models\Company\Company;
use Illuminate\Support\Facades\Route;

/**
 * NOTE:
 * - Keep this file in your autoload "files" (composer.json) and run: composer dump-autoload
 * - This helper set is intentionally simple and safe (no extra engineering).
 */

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        // Keep your current placeholder logic as-is.
        // If you enable settings later, you can restore the cached implementation.
        return $default;
    }
}

/**
 * Current selected company id (from session)
 */
if (!function_exists('active_company_id')) {
    function active_company_id(): ?int
    {
        $id = session('current_company_id');
        return $id ? (int) $id : null;
    }
}

/**
 * Whether a company is selected
 */
if (!function_exists('company_selected')) {
    function company_selected(): bool
    {
        return (bool) active_company_id();
    }
}

/**
 * Selected company model (minimal columns; avoids heavy loads)
 */
if (!function_exists('active_company')) {
    function active_company(): ?Company
    {
        static $memo = null;
        static $loaded = false;

        if ($loaded) {
            return $memo;
        }

        $loaded = true;

        $id = active_company_id();
        if (!$id) return $memo = null;

        return $memo = Company::query()
            ->select('id', 'uuid', 'name', 'code', 'company_status_id', 'logo')
            ->find($id);
    }
}
/**
 * Whether current user can switch company (has > 1 company)
 */
if (!function_exists('can_switch_company')) {
    function can_switch_company(): bool
    {
        static $memo = null;
        if ($memo !== null) return $memo;

        $user = auth()->user();
        if (!$user) return $memo = false;

        return $memo = ($user->companies()->count() > 1);
    }
}

/**
 * Get all companies for current user (for dropdowns in footer/header if needed)
 */
if (!function_exists('my_companies')) {
    function my_companies()
    {
        static $memo = null;
        static $loaded = false;

        if ($loaded) return $memo;

        $loaded = true;

        $user = auth()->user();
        if (!$user) return $memo = collect();

        return $memo = $user->companies()
            ->select('companies.id', 'companies.uuid', 'companies.name', 'companies.code')
            ->orderBy('companies.name')
            ->get();
    }
}

/**
 * Safe route generator for company-scoped routes.
 * - returns fallback if route missing OR company missing
 * - never throws RouteNotFoundException
 *
 * Usage:
 *  company_route('company.dashboard')  // auto injects company uuid
 *  company_route('company.settings.roles.index')
 */
if (!function_exists('company_route')) {
    function company_route(string $name, array $params = [], string $fallback = '#'): string
    {
        if (!Route::has($name)) {
            return $fallback;
        }

        $company = active_company();
        if (!$company) {
            return $fallback;
        }

        return route($name, array_merge(['company' => $company->uuid], $params));
    }
}

/**
 * Safe route generator for non-company routes (settings/auth/etc)
 */
if (!function_exists('safe_route')) {
    function safe_route(string $name, array $params = [], string $fallback = '#'): string
    {
        return Route::has($name) ? route($name, $params) : $fallback;
    }
}

/**
 * Convenience: active company name/code for UI
 */
if (!function_exists('active_company_label')) {
    function active_company_label(string $fallback = 'No company selected'): string
    {
        $c = active_company();
        if (!$c) return $fallback;

        $code = $c->code ? " ({$c->code})" : '';
        return $c->name . $code;
    }
}
