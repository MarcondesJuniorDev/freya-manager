<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::with('brand');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ean', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->has('stock_filter')) {
            $filter = $request->input('stock_filter');
            if ($filter === 'low') {
                $query->whereColumn('stock_quantity', '<=', 'min_stock_quantity')
                      ->where('stock_quantity', '>', 0);
            } elseif ($filter === 'out') {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'brands' => $brands,
            'filters' => $request->only(['search', 'brand_id', 'stock_filter']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ean' => 'nullable|string|unique:products,ean|max:255',
            'sku' => 'nullable|string|unique:products,sku|max:255',
            'cost_price' => 'required|numeric|min:0',
            'catalog_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_quantity' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);

        Product::create($validated);

        return redirect()->back()->with('success', 'Produto adicionado com sucesso.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ean' => 'nullable|string|unique:products,ean,' . $product->id . '|max:255',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id . '|max:255',
            'cost_price' => 'required|numeric|min:0',
            'catalog_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_quantity' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . $product->id;

        $product->update($validated);

        return redirect()->back()->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleItems()->count() > 0) {
            return redirect()->back()->withErrors(['error' => 'Não é possível excluir um produto que já possui vendas registradas.']);
        }

        $product->delete();

        return redirect()->back()->with('success', 'Produto excluído com sucesso.');
    }

    /**
     * Look up product by EAN (barcode) for camera scanner quick sale checkout.
     */
    public function lookupByEan(string $ean): JsonResponse
    {
        $product = Product::with('brand')
            ->where('ean', $ean)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Produto não encontrado ou inativo.'], 404);
        }

        return response()->json($product);
    }
}
