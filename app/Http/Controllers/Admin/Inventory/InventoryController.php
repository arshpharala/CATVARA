<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Catalog\ProductVariant;
use App\Models\Company\Company;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryReason;
use App\Models\Inventory\InventoryTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    protected $postingService;

    public function __construct(\App\Services\Inventory\InventoryPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /**
     * Dashboard with stats and recent data.
     */
    public function index(Request $request)
    {
        $companyId = $request->company->id;
        $locations = InventoryLocation::where('company_id', $companyId)->with('locatable')->get();

        // Stats
        $stats = [
            'total_skus' => InventoryBalance::where('company_id', $companyId)->distinct('product_variant_id')->count(),
            'total_units' => (int) InventoryBalance::where('company_id', $companyId)->sum('quantity'),
            'low_stock' => InventoryBalance::where('company_id', $companyId)->where('quantity', '>', 0)->where('quantity', '<=', 10)->count(),
            'out_of_stock' => InventoryBalance::where('company_id', $companyId)->where('quantity', '<=', 0)->count(),
        ];

        // Recent Transfers
        $recentTransfers = InventoryTransfer::where('company_id', $companyId)
            ->with(['fromLocation.locatable', 'toLocation.locatable', 'status'])
            ->latest()
            ->take(5)
            ->get();

        return view('theme.adminlte.inventory.index', compact('locations', 'stats', 'recentTransfers'));
    }

    /**
     * DataTables endpoint for balances.
     */
    public function balancesData(Request $request)
    {
        $query = InventoryBalance::where('company_id', $request->company->id)
            ->with(['productVariant.product', 'location.locatable']);

        if ($request->filled('location_id')) {
            $query->where('inventory_location_id', $request->location_id);
        }

        return DataTables::of($query)
            ->addColumn('sku', fn($r) => $r->productVariant->sku ?? '-')
            ->addColumn('product_name', fn($r) => $r->productVariant->product->name ?? '-')
            ->addColumn('location_name', fn($r) => $r->location->locatable->name ?? $r->location->type)
            ->editColumn('quantity', fn($r) => '<span class="badge badge-' . ($r->quantity > 0 ? 'success' : 'danger') . '">' . (float)$r->quantity . '</span>')
            ->addColumn('last_movement', fn($r) => $r->last_movement_at ? $r->last_movement_at->diffForHumans() : '-')
            ->addColumn('actions', function($r) {
                $adjustUrl = company_route('company.inventory.inventory.create') . '?variant=' . $r->product_variant_id . '&location=' . $r->inventory_location_id;
                return '<a href="' . $adjustUrl . '" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i> Adjust</a>';
            })
            ->rawColumns(['quantity', 'actions'])
            ->make(true);
    }

    /**
     * Show form to adjust stock.
     */
    public function create()
    {
        $locations = InventoryLocation::where('company_id', request()->company->id)->with('locatable')->get();

        // Simple list of products for dropdown - in real app might need AJAX search
        $variants = ProductVariant::where('company_id', request()->company->id)
            ->with(['product', 'attributeValues'])
            ->get();

        // Get reasons (Adjustment, Purchase, etc)
        // If not seeded, we might need fallback or seed them.
        $reasons = InventoryReason::where('is_active', true)->get();

        return view('theme.adminlte.inventory.adjust', compact('locations', 'variants', 'reasons'));
    }

    /**
     * Process stock adjustment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'inventory_location_id' => 'required|exists:inventory_locations,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:add,remove',
            'reason' => 'nullable|string',
            'redirect_to' => 'nullable|url',
        ]);

        try {
            DB::beginTransaction();

            // Ensure Manual Reason exists
            InventoryReason::firstOrCreate(
                ['company_id' => $request->company->id, 'code' => 'MANUAL'],
                ['name' => 'Manual Adjustment', 'is_active' => true]
            );

            // Calculate signed quantity (Not needed for service usually if we use reason props,
            // but InventoryPostingService calculates sign based on REASON is_increase.
            // Wait, InventoryPostingService line 46: $signedQty = $reason->is_increase ? abs($q) : -abs($q);
            // So if I pass 'MANUAL' (type=adjustment), I need to check if 'MANUAL' is increase or decrease?
            // Actually, "Adjustment" type isn't inherently + or -.
            // I should have two reasons: 'MANUAL_ADD' (increase=true) and 'MANUAL_REMOVE' (increase=false).
            // OR I just pass a quantity to the service?
            // Let's re-read Service line 39: `InventoryReason::where('code', $data['reason_code'])`.
            // Line 46: `$signedQty = $reason->is_increase ? abs... : -abs...`
            // So the Service ENFORCES the sign based on the Reason configuration. This is strict audit.
            // Therefore, I cannot just pass + or - with the same reason code "MANUAL".
            // I MUST have two reasons: "STOCK_IN" (or similar) and "STOCK_OUT".

            $reasonCode = $request->type === 'add' ? 'STOCK_IN' : 'STOCK_OUT';

            // Ensure these standard reasons exist
            if ($request->type === 'add') {
                InventoryReason::firstOrCreate(
                    ['company_id' => $request->company->id, 'code' => 'STOCK_IN'],
                    ['name' => 'Stock In (Manual)', 'is_increase' => true, 'is_active' => true]
                );
            } else {
                InventoryReason::firstOrCreate(
                    ['company_id' => $request->company->id, 'code' => 'STOCK_OUT'],
                    ['name' => 'Stock Out (Manual)', 'is_increase' => false, 'is_active' => true]
                );
            }

            $movement = $this->postingService->postMovement([
                'company_id' => $request->company->id,
                'inventory_location_id' => $request->inventory_location_id,
                'product_variant_id' => $request->product_variant_id,
                'reason_code' => $reasonCode,
                'quantity' => $request->quantity, // Service takes abs via reason logic
                'performed_by' => auth()->id(),
                'reference_type' => 'manual_adjustment',
                'reference_id' => null, // Could ideally store a ManualAdjustment record ID but simple movement is OK for now
                'idempotency_key' => Str::uuid(), // Unique per request
                'allow_negative' => true, // Manual adjust override? Or strictly enforce? User said "Enterprise Grade".
                // Usually manual adjust allows correcting negatives or creating them if counting errors.
            ]);

            DB::commit();

            if ($request->has('redirect_to')) {
                return redirect($request->redirect_to)->with('success', 'Stock adjusted successfully.');
            }

            return redirect(company_route('company.inventory.index'))
                ->with('success', 'Stock adjusted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error adjusting stock: '.$e->getMessage());
        }
    }

    /**
     * Process quick transfer (Immediate).
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'from_location_id' => 'required|exists:inventory_locations,id',
            'to_location_id' => 'required|exists:inventory_locations,id|different:from_location_id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|numeric|min:0.01',
            'redirect_to' => 'nullable|url',
        ]);

        try {
            // Ensure transfer reasons exist (company-scoped)
            InventoryReason::firstOrCreate(
                ['company_id' => $request->company->id, 'code' => 'TRANSFER_OUT'],
                ['name' => 'Transfer Out', 'is_increase' => false, 'is_active' => true]
            );
            InventoryReason::firstOrCreate(
                ['company_id' => $request->company->id, 'code' => 'TRANSFER_IN'],
                ['name' => 'Transfer In', 'is_increase' => true, 'is_active' => true]
            );

            // Use the SERVICE for atomic transfer
            $this->postingService->postTransfer([
                'company_id' => $request->company->id,
                'from_location_id' => $request->from_location_id,
                'to_location_id' => $request->to_location_id,
                'product_variant_id' => $request->product_variant_id,
                'quantity' => $request->quantity,
                'transfer_id' => uniqid('qt_'), // Quick Transfer Reference
                'performed_by' => auth()->id(),
            ]);

            if ($request->has('redirect_to')) {
                return redirect($request->redirect_to)->with('success', 'Stock transferred successfully.');
            }

            return back()->with('success', 'Stock transferred successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Transfer failed: '.$e->getMessage());
        }
    }

    /**
     * Movement History (Audit Log).
     */
    public function movements(Request $request)
    {
        if ($request->ajax()) {
            $query = InventoryMovement::where('company_id', $request->company->id)
                ->with(['productVariant.product', 'location.locatable', 'reason', 'performer']);

            if ($request->filled('location_id')) {
                $query->where('inventory_location_id', $request->location_id);
            }
            
            if ($request->filled('product_variant_id')) {
                $query->where('product_variant_id', $request->product_variant_id);
            }

            return DataTables::of($query)
                ->addColumn('sku', fn($r) => $r->productVariant->sku ?? '-')
                ->addColumn('location_name', fn($r) => $r->location->locatable->name ?? $r->location->type ?? '-')
                ->addColumn('reason_name', fn($r) => $r->reason->name ?? '-')
                ->editColumn('quantity', fn($r) => '<span class="badge badge-' . ($r->quantity > 0 ? 'success' : 'danger') . '">' . ($r->quantity > 0 ? '+' : '') . (float)$r->quantity . '</span>')
                ->addColumn('performed_by_name', fn($r) => $r->performer->name ?? '-')
                ->addColumn('date', fn($r) => $r->occurred_at ? $r->occurred_at->format('M d, Y H:i') : '-')
                ->rawColumns(['quantity'])
                ->make(true);
        }

        $locations = InventoryLocation::where('company_id', $request->company->id)->with('locatable')->get();

        return view('theme.adminlte.inventory.movements', compact('locations'));
    }

    /**
     * Variant Specific Inventory Details
     */
    public function variantDetails(Request $request, Company $company, $id)
    {
        // Using ID and finding manually to ensure company scope
        $variant = ProductVariant::where('company_id', $company->id)
            ->with(['product', 'attributeValues', 'prices.currency', 'prices.priceChannel'])
            ->findOrFail($id);

        $locations = InventoryLocation::where('company_id', $company->id)->with('locatable')->get();
        
        // Balances
        $balances = InventoryBalance::where('product_variant_id', $variant->id)
            ->where('company_id', $company->id)
            ->with('location.locatable')
            ->get();

        // One-time flash data for breadcrumbs or context if needed?
        
        // Pass to view
        return view('theme.adminlte.inventory.variant_details', compact('variant', 'locations', 'balances'));
    }
}
