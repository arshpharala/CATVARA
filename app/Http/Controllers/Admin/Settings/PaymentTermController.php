<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PaymentTerm;
use App\Http\Requests\Settings\StorePaymentTermRequest;
use App\Http\Requests\Settings\UpdatePaymentTermRequest;
use Illuminate\Http\Request;

class PaymentTermController extends Controller
{
    public function index()
    {
        $paymentTerms = PaymentTerm::all();
        return view('theme.adminlte.settings.payment_terms.index', compact('paymentTerms'));
    }

    public function create()
    {
        return view('theme.adminlte.settings.payment_terms.create');
    }

    public function store(StorePaymentTermRequest $request)
    {
        PaymentTerm::create($request->validated() + ['is_active' => $request->has('is_active')]);
        return redirect()->route('payment-terms.index')->with('success', 'Payment Term created successfully.');
    }

    public function edit(PaymentTerm $payment_term) // Ensure param matches route binding
    {
        return view('theme.adminlte.settings.payment_terms.edit', compact('payment_term'));
    }

    public function update(UpdatePaymentTermRequest $request, PaymentTerm $payment_term)
    {
        $payment_term->update($request->validated() + ['is_active' => $request->has('is_active')]);
        return redirect()->route('payment-terms.index')->with('success', 'Payment Term updated successfully.');
    }

    public function destroy(PaymentTerm $payment_term)
    {
        $payment_term->delete();
        return redirect()->route('payment-terms.index')->with('success', 'Payment Term deleted successfully.');
    }
}
