<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::where('company_id', $request->company->id)->paginate(10);
        return view('theme.adminlte.inventory.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('theme.adminlte.inventory.warehouses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $warehouse = new Warehouse();
        $warehouse->uuid = Str::uuid();
        $warehouse->company_id = $request->company->id;
        $warehouse->name = $request->name;
        $warehouse->code = $request->code;
        $warehouse->address = $request->address;
        $warehouse->phone = $request->phone;
        $warehouse->is_active = $request->has('is_active');
        $warehouse->save();

        // Create associated Inventory Location
        $location = new InventoryLocation();
        $location->uuid = Str::uuid();
        $location->company_id = $request->company->id;
        $location->locatable_type = Warehouse::class;
        $location->locatable_id = $warehouse->id;
        $location->type = 'warehouse';
        $location->is_active = $warehouse->is_active;
        $location->save();

        return redirect(company_route('company.inventory.warehouses.index'))
            ->with('success', 'Warehouse created successfully.');
    }

    public function edit(Company $company, Warehouse $warehouse)
    {
        if ($warehouse->company_id !== $company->id) {
            abort(403);
        }
        return view('theme.adminlte.inventory.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Company $company, Warehouse $warehouse)
    {
        if ($warehouse->company_id !== $company->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $warehouse->name = $request->name;
        $warehouse->code = $request->code;
        $warehouse->address = $request->address;
        $warehouse->phone = $request->phone;
        $warehouse->is_active = $request->has('is_active');
        $warehouse->save();

        // Update Location is_active status
        if ($warehouse->inventoryLocation) {
            $warehouse->inventoryLocation->is_active = $warehouse->is_active;
            $warehouse->inventoryLocation->save();
        }

        return redirect(company_route('company.inventory.warehouses.index'))
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Company $company, Warehouse $warehouse)
    {
         if ($warehouse->company_id !== $company->id) {
            abort(403);
        }
        
        try {
            // Check if stock exists? For now assume soft delete or force delete logic
            // Ideally we check balances first.
            if($warehouse->inventoryLocation && $warehouse->inventoryLocation->balances()->sum('quantity') > 0) {
                 return back()->with('error', 'Cannot delete warehouse with active stock.');
            }
            
            if($warehouse->inventoryLocation) {
                $warehouse->inventoryLocation->delete();
            }
            $warehouse->delete();
            
            return redirect()->back()->with('success', 'Warehouse deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting warehouse.');
        }
    }
}
