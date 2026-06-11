<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        $brands = Brand::withCount('products')->get();
        return Inertia::render('Brands/Index', [
            'brands' => $brands
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:brands,name|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Brand::create($validated);

        return redirect()->back()->with('success', 'Marca criada com sucesso.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:brands,name,' . $brand->id . '|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $brand->update($validated);

        return redirect()->back()->with('success', 'Marca atualizada com sucesso.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->count() > 0) {
            return redirect()->back()->withErrors(['error' => 'Não é possível excluir uma marca que possui produtos associados.']);
        }

        $brand->delete();

        return redirect()->back()->with('success', 'Marca excluída com sucesso.');
    }
}
