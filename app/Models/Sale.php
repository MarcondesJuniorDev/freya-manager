<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sale_date',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'total_cost',
        'payment_status',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<CustomerTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    /**
     * Get the financial profit of the sale.
     */
    public function getProfitAttribute(): float
    {
        return (float) ($this->total_amount - $this->total_cost);
    }

    /**
     * Get the profit margin percentage of the sale.
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0.00;
        }

        return round(($this->profit / $this->total_amount) * 100, 2);
    }
}
