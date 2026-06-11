<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'description',
        'ean',
        'sku',
        'cost_price',
        'catalog_price',
        'sale_price',
        'stock_quantity',
        'min_stock_quantity',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'catalog_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SaleItem, $this>
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_quantity && $this->stock_quantity > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity <= 0;
    }
}
