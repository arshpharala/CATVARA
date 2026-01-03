<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Company\Company; // Added for type hinting in getAttributes and edit/destroy methods

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::where('company_id', $request->company->id);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->with('parent')->paginate(10);

        return view('theme.adminlte.catalog.categories.index', compact('categories'));
    }

    public function create()
    {
        $categories = Category::where('company_id', request()->company->id)->with('children')->get();
        // Load all attributes for selection
        $attributes = \App\Models\Catalog\Attribute::where('company_id', request()->company->id)->get();
        
        return view('theme.adminlte.catalog.categories.form', compact('categories', 'attributes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'attributes' => 'array',
            'attributes.*' => 'exists:attributes,id'
        ]);

        $category = new Category();
        $category->uuid = Str::uuid();
        $category->company_id = $request->company->id;
        $category->parent_id = $request->parent_id;
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $category->is_active = $request->has('is_active');
        $category->save();
        
        // Sync Attributes
        if ($request->has('attributes')) {
            $category->attributes()->sync($request->attributes);
        }

        return redirect(company_route('company.catalog.categories.index'))
            ->with('success', 'Category created successfully.');
    }

    public function edit(Company $company, Category $category)
    {
        if ($category->company_id !== $company->id) {
            abort(403);
        }
        $categories = Category::where('company_id', $company->id)
            ->where('id', '!=', $category->id) // Prevent self-parenting loop
            ->get();
            
        // Load all attributes for selection
        $attributes = \App\Models\Catalog\Attribute::where('company_id', $company->id)->get();

        return view('theme.adminlte.catalog.categories.form', compact('category', 'categories', 'attributes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company, Category $category)
    {
        if ($category->company_id !== $company->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'attributes' => 'array',
            'attributes.*' => 'exists:attributes,id'
        ]);

        $category->parent_id = $request->parent_id;
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $category->is_active = $request->has('is_active');
        $category->save();

        // Sync Attributes
        if ($request->has('attributes')) {
            $category->attributes()->sync($request->attributes);
        } else {
            $category->attributes()->detach(); // If no attributes are selected, remove all
        }

        return redirect(company_route('company.catalog.categories.index'))
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Company $company, Category $category)
    {
         if ($category->company_id !== $company->id) {
            abort(403);
        }
        
        // Check if has children or products... for now simple delete logic or catch exception
        try {
            $category->delete();
            return redirect()->back()->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Cannot delete category because it is in use.');
        }
    }
}
