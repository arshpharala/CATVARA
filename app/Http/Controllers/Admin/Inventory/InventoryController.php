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
use App\Http\Requests;
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
                // Link to Variant Details
                $url = company_route('company.inventory.variant.details', ['id' => $r->product_variant_id]);
                return '<a href="' . $url . '" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i> Manage</a>';
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
    public function store(Requests\Inventory\StoreStockAdjustmentRequest $request)
    {
        try {
            DB::beginTransaction();

            // Determine reason code based on type
            // ... (rest of logic same) ...

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
                'unit_cost' => ProductVariant::find($request->product_variant_id)->cost_price ?? 0,
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
    public function transfer(Requests\Inventory\CreateQuickTransferRequest $request)
    {
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
                ->with(['variant.product', 'location.locatable', 'reason', 'performer']);

            if ($request->filled('location_id')) {
                $query->where('inventory_location_id', $request->location_id);
            }
            
            if ($request->filled('product_variant_id')) {
                $query->where('product_variant_id', $request->product_variant_id);
            }

            return DataTables::of($query)
                ->addColumn('sku', fn($r) => $r->variant->sku ?? '-')
                ->addColumn('location_name', fn($r) => $r->location->locatable->name ?? $r->location->type ?? '-')
                ->addColumn('reason_name', fn($r) => $r->reason->name ?? '-')
                ->addColumn('reference', function($r) {
                     // Basic formatting of reference
                     if($r->reference_type === 'inventory_transfer') {
                         return 'Transfer'; // Could link to it if we had the ID easily or loaded relation
                     }
                     return $r->reference_type ? class_basename($r->reference_type) : '-';
                })
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
        
        // Audit Trail Count
        $movementCount = InventoryMovement::where('product_variant_id', $variant->id)
            ->where('company_id', $company->id)
            ->count();
        
        // Pass to view
        return view('theme.adminlte.inventory.variant_details', compact('variant', 'locations', 'balances', 'movementCount'));
    }
}
