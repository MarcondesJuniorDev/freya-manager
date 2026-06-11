<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Brands
    Route::resource('brands', BrandController::class)->except(['create', 'show', 'edit']);
    
    // Products
    Route::get('products/lookup/{ean}', [ProductController::class, 'lookupByEan'])->name('products.lookup');
    Route::resource('products', ProductController::class);
    
    // Customers
    Route::post('customers/{customer}/transactions', [CustomerController::class, 'storeTransaction'])->name('customers.transactions.store');
    Route::resource('customers', CustomerController::class);
    
    // Sales
    Route::resource('sales', SaleController::class);
});

require __DIR__.'/settings.php';
