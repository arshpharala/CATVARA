<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Inventory\InventoryReason;
use Illuminate\Http\Request;

class InventoryReasonController extends Controller
{
    public function index(Request $request)
    {
        $reasons = InventoryReason::where('company_id', $request->company->id)->get();
        return view('theme.adminlte.inventory.reasons.index', compact('reasons'));
    }

    public function create()
    {
        return view('theme.adminlte.inventory.reasons.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'type' => 'required|in:in,out', // Map to is_increase
        ]);

        $reason = new InventoryReason();
        $reason->company_id = $request->company->id;
        $reason->name = $request->name;
        $reason->code = strtoupper($request->code);
        $reason->is_increase = $request->type === 'in';
        $reason->is_active = $request->has('is_active');
        $reason->save();

        return redirect(company_route('company.inventory.reasons.index'))
            ->with('success', 'Reason created successfully.');
    }

    public function edit(Company $company, InventoryReason $reason)
    {
        if ($reason->company_id !== $company->id) {
            abort(403);
        }
        return view('theme.adminlte.inventory.reasons.edit', compact('reason'));
    }

    public function update(Request $request, Company $company, InventoryReason $reason)
    {
        if ($reason->company_id !== $company->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'type' => 'required|in:in,out',
        ]);

        $reason->name = $request->name;
        $reason->code = strtoupper($request->code);
        $reason->is_increase = $request->type === 'in';
        $reason->is_active = $request->has('is_active');
        $reason->save();

        return redirect(company_route('company.inventory.reasons.index'))
            ->with('success', 'Reason updated successfully.');
    }

    public function destroy(Company $company, InventoryReason $reason)
    {
        if ($reason->company_id !== $company->id) {
            abort(403);
        }

        try {
            $reason->delete();
            return back()->with('success', 'Reason deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting reason.');
        }
    }
}
