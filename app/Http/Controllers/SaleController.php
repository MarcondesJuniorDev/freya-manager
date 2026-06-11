<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['customer', 'items.product.brand'])
            ->orderBy('sale_date', 'desc')
            ->paginate(15);

        return Inertia::render('Sales/Index', [
            'sales' => $sales
        ]);
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('brand')
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get();

        return Inertia::render('Sales/Create', [
            'customers' => $customers,
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'discount_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,pix,fiado',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validated['payment_method'] === 'fiado' && empty($validated['customer_id'])) {
            return redirect()->back()->withErrors(['customer_id' => 'É necessário selecionar um cliente para a opção de pagamento Fiado.']);
        }

        try {
            $sale = DB::transaction(function () use ($validated) {
                $subtotalAmount = 0.00;
                $totalCost = 0.00;
                $saleItems = [];

                // 1. Process items first to calculate subtotals and check inventory
                foreach ($validated['items'] as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);

                    if ($product->stock_quantity < $itemData['quantity']) {
                        throw new \Exception("Estoque insuficiente para o produto: {$product->name}");
                    }

                    $unitPrice = $product->sale_price;
                    $unitCost = $product->cost_price;
                    $subtotal = $unitPrice * $itemData['quantity'];

                    $subtotalAmount += $subtotal;
                    $totalCost += ($unitCost * $itemData['quantity']);

                    $saleItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $unitPrice,
                        'unit_cost' => $unitCost,
                        'subtotal' => $subtotal,
                    ];
                }

                $discountAmount = $validated['discount_amount'];
                $totalAmount = max(0.00, $subtotalAmount - $discountAmount);
                $paymentStatus = $validated['payment_method'] === 'fiado' ? 'pending' : 'paid';

                // 2. Create the Sale
                $sale = Sale::create([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'sale_date' => now(),
                    'subtotal_amount' => $subtotalAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'total_cost' => $totalCost,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                // 3. Save Sale Items and decrement inventory
                foreach ($saleItems as $sItem) {
                    $sale->items()->create($sItem);

                    $product = Product::find($sItem['product_id']);
                    $product->decrement('stock_quantity', $sItem['quantity']);
                }

                // 4. Add Debit to Customer caderneta if payment method is "fiado"
                if ($validated['payment_method'] === 'fiado') {
                    $customer = Customer::findOrFail($validated['customer_id']);
                    $customer->addDebit(
                        $totalAmount,
                        "Compra a prazo (Fiado) - Venda #{$sale->id}",
                        $sale->id
                    );
                }

                return $sale;
            });

            return redirect()->route('sales.index')->with('success', 'Venda realizada com sucesso.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'items.product.brand']);
        return Inertia::render('Sales/Show', [
            'sale' => $sale
        ]);
    }
}
