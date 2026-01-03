<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $stores = Store::where('company_id', $request->company->id)->paginate(10);
        return view('theme.adminlte.inventory.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('theme.adminlte.inventory.stores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $store = new Store();
        $store->uuid = Str::uuid();
        $store->company_id = $request->company->id;
        $store->name = $request->name;
        $store->code = $request->code;
        $store->address = $request->address;
        $store->phone = $request->phone;
        $store->is_active = $request->has('is_active');
        $store->save();

        // Create associated Inventory Location
        $location = new InventoryLocation();
        $location->uuid = Str::uuid();
        $location->company_id = $request->company->id;
        // Correct Polymorphic relation
        $location->locatable_type = Store::class;
        $location->locatable_id = $store->id;
        $location->type = 'store'; 
        $location->is_active = $store->is_active;
        $location->save();

        return redirect(company_route('company.inventory.stores.index'))
            ->with('success', 'Store created successfully.');
    }

    public function edit(Company $company, Store $store)
    {
        if ($store->company_id !== $company->id) {
            abort(403);
        }
        return view('theme.adminlte.inventory.stores.edit', compact('store'));
    }

    public function update(Request $request, Company $company, Store $store)
    {
        if ($store->company_id !== $company->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $store->name = $request->name;
        $store->code = $request->code;
        $store->address = $request->address;
        $store->phone = $request->phone;
        $store->is_active = $request->has('is_active');
        $store->save();

        // Update Location is_active status
        if ($store->inventoryLocation) {
            $store->inventoryLocation->is_active = $store->is_active;
            $store->inventoryLocation->save();
        }

        return redirect(company_route('company.inventory.stores.index'))
            ->with('success', 'Store updated successfully.');
    }

    public function destroy(Company $company, Store $store)
    {
         if ($store->company_id !== $company->id) {
            abort(403);
        }
        
        try {
            // Check balances logic would go here
             if($store->inventoryLocation && $store->inventoryLocation->balances()->sum('quantity') > 0) {
                 return back()->with('error', 'Cannot delete store with active stock.');
            }

            if($store->inventoryLocation) {
                $store->inventoryLocation->delete();
            }
            $store->delete();
            
            return redirect()->back()->with('success', 'Store deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting store.');
        }
    }
}
