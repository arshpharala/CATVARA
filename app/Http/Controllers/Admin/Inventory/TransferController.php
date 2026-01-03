<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryReason;
use App\Models\Inventory\InventoryTransfer;
use App\Models\Inventory\InventoryTransferItem;
use App\Models\Inventory\InventoryTransferStatus;
use App\Models\Catalog\ProductVariant;
use App\Services\Inventory\InventoryTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    protected $transferService;

    public function __construct(InventoryTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * List transfers with DataTable support.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = InventoryTransfer::where('company_id', $request->company->id)
                ->with(['fromLocation.locatable', 'toLocation.locatable', 'status']);

            return DataTables::of($query)
                ->addColumn('from', fn($r) => $r->fromLocation->locatable->name ?? '-')
                ->addColumn('to', fn($r) => $r->toLocation->locatable->name ?? '-')
                ->addColumn('status_badge', fn($r) => '<span class="badge badge-' . $this->statusColor($r->status->code) . '">' . $r->status->name . '</span>')
                ->addColumn('items_count', fn($r) => $r->items->count())
                ->addColumn('actions', function($r) {
                    return '<a href="' . company_route('company.inventory.transfers.show', $r) . '" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>';
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('admin.inventory.transfers.index');
    }

    private function statusColor($code): string
    {
        return match($code) {
            'DRAFT' => 'secondary',
            'APPROVED' => 'primary',
            'SHIPPED' => 'warning',
            'RECEIVED', 'CLOSED' => 'success',
            'CANCELLED' => 'danger',
            default => 'light'
        };
    }

    /**
     * Create transfer form.
     */
    public function create(Request $request)
    {
        $locations = InventoryLocation::where('company_id', $request->company->id)->with('locatable')->get();
        $variants = ProductVariant::where('company_id', $request->company->id)->with('product')->get();

        return view('admin.inventory.transfers.create', compact('locations', 'variants'));
    }

    /**
     * Store new transfer.
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_location_id' => 'required|exists:inventory_locations,id',
            'to_location_id' => 'required|exists:inventory_locations,id|different:from_location_id',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $draftStatus = InventoryTransferStatus::where('code', 'DRAFT')->first();

        $transfer = InventoryTransfer::create([
            'uuid' => Str::uuid(),
            'company_id' => $request->company->id,
            'reference' => 'TRF-' . strtoupper(Str::random(8)),
            'from_inventory_location_id' => $request->from_location_id,
            'to_inventory_location_id' => $request->to_location_id,
            'inventory_transfer_status_id' => $draftStatus->id,
            'created_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $item) {
            InventoryTransferItem::create([
                'inventory_transfer_id' => $transfer->id,
                'product_variant_id' => $item['variant_id'],
                'quantity_requested' => $item['quantity'],
                'quantity_shipped' => 0,
                'quantity_received' => 0,
            ]);
        }

        return redirect(company_route('company.inventory.transfers.show', $transfer))
            ->with('success', 'Transfer created successfully.');
    }

    /**
     * Show transfer details.
     */
    public function show(Request $request, $id)
    {
        $transfer = InventoryTransfer::where('company_id', $request->company->id)
            ->with(['fromLocation.locatable', 'toLocation.locatable', 'status', 'items.variant.product'])
            ->findOrFail($id);

        return view('admin.inventory.transfers.show', compact('transfer'));
    }

    /**
     * Approve transfer.
     */
    public function approve(Request $request, $id)
    {
        $transfer = InventoryTransfer::where('company_id', $request->company->id)->findOrFail($id);
        $this->transferService->approve($transfer);

        return back()->with('success', 'Transfer approved.');
    }

    /**
     * Ship transfer.
     */
    public function ship(Request $request, $id)
    {
        $transfer = InventoryTransfer::where('company_id', $request->company->id)->findOrFail($id);
        $this->transferService->ship($transfer, auth()->id());

        return back()->with('success', 'Transfer shipped. Stock deducted from source.');
    }

    /**
     * Receive transfer.
     */
    public function receive(Request $request, $id)
    {
        $transfer = InventoryTransfer::where('company_id', $request->company->id)->findOrFail($id);
        
        // For full receive, pass all items as received
        $receivedItems = [];
        foreach ($transfer->items as $item) {
            $receivedItems[$item->id] = $item->quantity_shipped;
        }

        $this->transferService->receive($transfer, $receivedItems, auth()->id());

        return back()->with('success', 'Transfer received. Stock added to destination.');
    }
}
