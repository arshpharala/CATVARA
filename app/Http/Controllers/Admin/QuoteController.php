<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuoteStoreRequest;
use App\Http\Requests\Admin\QuoteUpdateRequest;
use App\Models\Sales\Quote;
use App\Models\Sales\QuoteStatus;
use App\Models\Customer\Customer;
use App\Models\Pricing\Currency;
use App\Models\Accounting\PaymentTerm;
use App\Models\Company\Company;
use App\Services\Sales\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class QuoteController extends Controller
{
    public function __construct(
        protected QuoteService $quoteService
    ) {
    }

    /**
     * Display a listing of quotes.
     */
    public function index(Request $request, Company $company)
    {
        if ($request->ajax()) {

            $query = Quote::query()
                ->select(
                    'quotes.id',
                    'quotes.uuid',
                    'quotes.quote_number',
                    'quotes.customer_id',
                    'quotes.status_id',
                    'quotes.grand_total',
                    'quotes.valid_until',
                    'quotes.created_at',
                    'quote_statuses.name as status_name',
                    'quote_statuses.code as status_code',
                    'customers.display_name as customer_name'
                )
                ->leftJoin('quote_statuses', 'quote_statuses.id', '=', 'quotes.status_id')
                ->leftJoin('customers', 'customers.id', '=', 'quotes.customer_id')
                ->where('quotes.company_id', $company->id);

            // Filters
            if ($request->filled('status_id')) {
                $query->where('quotes.status_id', $request->status_id);
            }

            if ($request->filled('customer_id')) {
                $query->where('quotes.customer_id', $request->customer_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('quote_number', function ($row) use ($company) {
                    $url = route('quotes.show', [$company->uuid, $row->id]);
                    return '<a href="' . $url . '" class="font-weight-bold">' . e($row->quote_number) . '</a>';
                })

                ->editColumn('customer_name', function ($row) {
                    return $row->customer_name
                        ? e($row->customer_name)
                        : '<span class="text-muted">Walk-in</span>';
                })

                ->addColumn('status_badge', function ($row) {
                    $code = strtoupper($row->status_code ?? '');
                    $badge = 'secondary';

                    if ($code === 'DRAFT')
                        $badge = 'secondary';
                    if ($code === 'SENT')
                        $badge = 'info';
                    if ($code === 'ACCEPTED')
                        $badge = 'success';
                    if ($code === 'EXPIRED')
                        $badge = 'warning';
                    if ($code === 'CANCELLED')
                        $badge = 'danger';

                    return '<span class="badge badge-' . $badge . '">' . e($row->status_name ?? $code) . '</span>';
                })

                ->editColumn('grand_total', function ($row) {
                    return '<span class="font-weight-bold">' . number_format($row->grand_total, 2) . '</span>';
                })

                ->editColumn('valid_until', function ($row) {
                    if (!$row->valid_until)
                        return '<span class="text-muted">—</span>';

                    $date = \Carbon\Carbon::parse($row->valid_until);
                    $isExpired = $date->isPast();
                    $class = $isExpired ? 'text-danger' : '';

                    return '<span class="' . $class . '">' . $date->format('d-M-Y') . '</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? \Carbon\Carbon::parse($row->created_at)->format('d-M-Y')
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('action', function ($row) use ($company) {
                    $compact['showUrl'] = route('quotes.show', [$company->uuid, $row->id]);
                    $compact['editUrl'] = route('quotes.edit', [$company->uuid, $row->id]);
                    $compact['deleteUrl'] = null;
                    $compact['editSidebar'] = false;

                    return view('theme.adminlte.components._table-actions', $compact)->render();
                })

                ->rawColumns([
                    'quote_number',
                    'customer_name',
                    'status_badge',
                    'grand_total',
                    'valid_until',
                    'created_at',
                    'action'
                ])
                ->make(true);
        }

        $statuses = QuoteStatus::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get(['id', 'display_name']);

        return view('theme.adminlte.quotes.index', compact('company', 'statuses', 'customers'));
    }

    /**
     * Stats API for dashboard cards
     */
    public function stats(Request $request, Company $company)
    {
        $baseQuery = Quote::where('company_id', $company->id);

        $all = (clone $baseQuery)->count();

        $draftId = QuoteStatus::where('code', 'DRAFT')->value('id');
        $sentId = QuoteStatus::where('code', 'SENT')->value('id');
        $acceptedId = QuoteStatus::where('code', 'ACCEPTED')->value('id');
        $expiredId = QuoteStatus::where('code', 'EXPIRED')->value('id');

        $draft = $draftId ? (clone $baseQuery)->where('status_id', $draftId)->count() : 0;
        $sent = $sentId ? (clone $baseQuery)->where('status_id', $sentId)->count() : 0;
        $accepted = $acceptedId ? (clone $baseQuery)->where('status_id', $acceptedId)->count() : 0;
        $expired = $expiredId ? (clone $baseQuery)->where('status_id', $expiredId)->count() : 0;

        return response()->json([
            'all_quotes' => $all,
            'draft_quotes' => $draft,
            'sent_quotes' => $sent,
            'accepted_quotes' => $accepted,
            'expired_quotes' => $expired,
        ]);
    }

    /**
     * Show the form for creating a new quote.
     */
    public function create(Company $company)
    {
        $customers = Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get(['id', 'display_name']);

        $currencies = Currency::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        $paymentTerms = PaymentTerm::query()
            ->select('payment_terms.*')
            ->join('company_payment_terms', 'company_payment_terms.payment_term_id', '=', 'payment_terms.id')
            ->where('company_payment_terms.company_id', $company->id)
            ->where('payment_terms.is_active', true)
            ->orderBy('payment_terms.name')
            ->get();

        return view('theme.adminlte.quotes.create', compact('company', 'customers', 'currencies', 'paymentTerms'));
    }

    /**
     * Store a newly created quote.
     */
    public function store(QuoteStoreRequest $request, Company $company)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Get payment term details if selected
            $paymentTermName = null;
            $paymentDueDays = null;

            if (!empty($data['payment_term_id'])) {
                $term = PaymentTerm::find($data['payment_term_id']);
                if ($term) {
                    $paymentTermName = $term->name;
                    $paymentDueDays = $term->due_days;
                }
            }

            $quote = $this->quoteService->createDraft([
                'company_id' => $company->id,
                'customer_id' => $data['customer_id'] ?? null,
                'currency_id' => $data['currency_id'],
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'payment_term_name' => $paymentTermName,
                'payment_due_days' => $paymentDueDays,
                'user_id' => auth()->id(),
            ]);

            // Update valid_until if provided
            if (!empty($data['valid_until'])) {
                $quote->update(['valid_until' => $data['valid_until']]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Quote Created Successfully',
                    'redirect' => route('quotes.show', [$company->uuid, $quote->id]),
                ]);
            }

            return redirect()
                ->route('quotes.show', [$company->uuid, $quote->id])
                ->with('success', 'Quote Created Successfully');

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Display the specified quote.
     */
    public function show(Company $company, string $id)
    {
        $quote = Quote::where('company_id', $company->id)
            ->with(['items', 'status'])
            ->findOrFail($id);

        $customer = $quote->customer_id
            ? Customer::find($quote->customer_id)
            : null;

        return view('theme.adminlte.quotes.show', compact('company', 'quote', 'customer'));
    }

    /**
     * Show the form for editing the quote.
     */
    public function edit(Company $company, string $id)
    {
        $quote = Quote::where('company_id', $company->id)->findOrFail($id);

        // Check if quote is editable
        if ($quote->status && $quote->status->is_final) {
            return redirect()
                ->route('quotes.show', [$company->uuid, $quote->id])
                ->with('error', 'This quote cannot be edited.');
        }

        $customers = Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get(['id', 'display_name']);

        $currencies = Currency::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        $paymentTerms = PaymentTerm::query()
            ->select('payment_terms.*')
            ->join('company_payment_terms', 'company_payment_terms.payment_term_id', '=', 'payment_terms.id')
            ->where('company_payment_terms.company_id', $company->id)
            ->where('payment_terms.is_active', true)
            ->orderBy('payment_terms.name')
            ->get();

        $statuses = QuoteStatus::where('is_active', true)->orderBy('name')->get();

        return view('theme.adminlte.quotes.edit', compact('company', 'quote', 'customers', 'currencies', 'paymentTerms', 'statuses'));
    }

    /**
     * Update the specified quote.
     */
    public function update(QuoteUpdateRequest $request, Company $company, string $id)
    {
        $quote = Quote::where('company_id', $company->id)->findOrFail($id);
        $data = $request->validated();

        // Check if quote is editable
        if ($quote->status && $quote->status->is_final) {
            if ($request->ajax()) {
                return response()->json(['message' => 'This quote cannot be edited.'], 422);
            }
            return back()->with('error', 'This quote cannot be edited.');
        }

        DB::beginTransaction();

        try {
            // Get payment term details if changed
            $paymentTermName = $quote->payment_term_name;
            $paymentDueDays = $quote->payment_due_days;

            if (isset($data['payment_term_id']) && $data['payment_term_id'] != $quote->payment_term_id) {
                $term = PaymentTerm::find($data['payment_term_id']);
                if ($term) {
                    $paymentTermName = $term->name;
                    $paymentDueDays = $term->due_days;
                }
            }

            $quote->update([
                'customer_id' => $data['customer_id'] ?? null,
                'currency_id' => $data['currency_id'] ?? $quote->currency_id,
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'payment_term_name' => $paymentTermName,
                'payment_due_days' => $paymentDueDays,
                'valid_until' => $data['valid_until'] ?? $quote->valid_until,
                'status_id' => $data['status_id'] ?? $quote->status_id,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Quote Updated Successfully',
                    'redirect' => route('quotes.show', [$company->uuid, $quote->id]),
                ]);
            }

            return redirect()
                ->route('quotes.show', [$company->uuid, $quote->id])
                ->with('success', 'Quote Updated Successfully');

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }
}
