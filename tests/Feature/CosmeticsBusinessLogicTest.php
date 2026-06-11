<?php

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\CustomerTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('brand can have products', function () {
    $brand = Brand::factory()->create(['name' => 'Natura Perfumes']);
    
    $product1 = Product::factory()->create([
        'brand_id' => $brand->id,
        'name' => 'Product A',
    ]);
    
    $product2 = Product::factory()->create([
        'brand_id' => $brand->id,
        'name' => 'Product B',
    ]);

    expect($brand->products)->toHaveCount(2);
    expect($brand->products->first()->name)->toBe('Product A');
});

test('product stock state helper methods work correctly', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'min_stock_quantity' => 3,
    ]);

    // Adequate stock
    expect($product->isLowStock())->toBeFalse();
    expect($product->isOutOfStock())->toBeFalse();

    // Low stock
    $product->stock_quantity = 2;
    expect($product->isLowStock())->toBeTrue();
    expect($product->isOutOfStock())->toBeFalse();

    // Out of stock
    $product->stock_quantity = 0;
    expect($product->isLowStock())->toBeFalse();
    expect($product->isOutOfStock())->toBeTrue();
});

test('customer balance changes are automatically tracked in the transaction ledger', function () {
    $customer = Customer::factory()->create([
        'balance' => 0.00,
    ]);

    // 1. Add debit (purchase via fiado)
    $debitTx = $customer->addDebit(150.50, 'Fiado purchase');
    
    $customer->refresh();
    expect($customer->balance)->toEqual(150.50);
    expect(CustomerTransaction::count())->toBe(1);
    expect($debitTx->type)->toBe('debit');
    expect($debitTx->amount)->toEqual(150.50);
    expect($debitTx->customer_id)->toBe($customer->id);

    // 2. Add credit (payment)
    $creditTx = $customer->addCredit(50.00, 'Partial PIX payment');
    
    $customer->refresh();
    expect($customer->balance)->toEqual(100.50);
    expect(CustomerTransaction::count())->toBe(2);
    expect($creditTx->type)->toBe('credit');
    expect($creditTx->amount)->toEqual(50.00);
});

test('sales calculates profit and profit margins correctly', function () {
    $product1 = Product::factory()->create([
        'cost_price' => 50.00,
        'sale_price' => 100.00,
    ]);
    
    $product2 = Product::factory()->create([
        'cost_price' => 30.00,
        'sale_price' => 50.00,
    ]);

    $sale = Sale::factory()->create([
        'subtotal_amount' => 150.00,
        'discount_amount' => 10.00,
        'total_amount' => 140.00,
        'total_cost' => 80.00, // 50 + 30
    ]);

    expect($sale->profit)->toEqual(60.00); // 140 - 80
    expect($sale->profit_margin)->toEqual(42.86); // round((60 / 140) * 100, 2)
});
