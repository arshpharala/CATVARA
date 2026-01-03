<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Pricing\Currency;
use App\Http\Requests\Settings\StoreCurrencyRequest;
use App\Http\Requests\Settings\UpdateCurrencyRequest;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::all();
        return view('theme.adminlte.settings.currencies.index', compact('currencies'));
    }

    public function create()
    {
        return view('theme.adminlte.settings.currencies.create');
    }

    public function store(StoreCurrencyRequest $request)
    {
        Currency::create($request->validated() + ['is_active' => $request->has('is_active')]);
        return redirect()->route('currencies.index')->with('success', 'Currency created successfully.');
    }

    public function edit(Currency $currency)
    {
        return view('theme.adminlte.settings.currencies.edit', compact('currency'));
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {
        $currency->update($request->validated() + ['is_active' => $request->has('is_active')]);
        return redirect()->route('currencies.index')->with('success', 'Currency updated successfully.');
    }

    public function destroy(Currency $currency)
    {
        // Add check if used? Most likely soft delete or restrict.
        // For now simple delete
        $currency->delete();
        return redirect()->route('currencies.index')->with('success', 'Currency deleted successfully.');
    }
}
