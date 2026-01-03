<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::where('company_id', $request->company->id)
            ->paginate(10);
        return view('theme.adminlte.catalog.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('theme.adminlte.catalog.attributes.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'values' => 'required|string' // Comma separated for MVP convenience
        ]);

        $attribute = new Attribute();
        $attribute->company_id = $request->company->id;
        $attribute->name = $request->name;
        $attribute->code = Str::slug($request->code);
        
         if (Attribute::where('company_id', $request->company->id)->where('code', $attribute->code)->exists()) {
             return back()->withErrors(['code' => 'Code already exists for this company.']);
        }
        
        $attribute->save();

        // Process values
        $values = array_map('trim', explode(',', $request->values));
        foreach ($values as $val) {
            if(!empty($val)) {
                $attribute->values()->create(['value' => $val]);
            }
        }

        return redirect(company_route('company.catalog.attributes.index'))
            ->with('success', 'Attribute saved successfully.');
    }

    public function edit(\App\Models\Company\Company $company, Attribute $attribute)
    {
         if ($attribute->company_id !== $company->id) {
            abort(403);
        }
        return view('theme.adminlte.catalog.attributes.form', compact('attribute'));
    }

    public function update(Request $request, \App\Models\Company\Company $company, Attribute $attribute)
    {
         if ($attribute->company_id !== $company->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'new_values' => 'nullable|string',
            'existing_values' => 'nullable|array'
        ]);

        $attribute->name = $request->name;
        $attribute->save();

        // 1. Update Existing Values (Status)
        if ($request->has('existing_values')) {
            foreach ($request->existing_values as $id => $data) {
                $val = AttributeValue::where('attribute_id', $attribute->id)->find($id);
                if ($val) {
                    $val->update([
                        'is_active' => isset($data['is_active'])
                    ]);
                }
            }
        }

        // 2. Add New Values
        if ($request->filled('new_values')) {
            $values = array_map('trim', explode(',', $request->new_values));
            $existing = $attribute->values()->pluck('value')->toArray();
            
            foreach ($values as $val) {
                // Determine case-insensitive duplicate check? Seeder did it, but let's be safe
                // For now, strict check
                if(!empty($val) && !in_array($val, $existing)) {
                    $attribute->values()->create([
                        'value' => $val,
                        'is_active' => true
                    ]);
                }
            }
        }

        return redirect(company_route('company.catalog.attributes.index'))
            ->with('success', 'Attribute updated successfully.');
    }
}
