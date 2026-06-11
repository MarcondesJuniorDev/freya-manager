<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'balance',
        'max_credit_limit',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'max_credit_limit' => 'decimal:2',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    /**
     * Add a debit (increases what the customer owes).
     */
    public function addDebit(float $amount, string $description, ?int $saleId = null): CustomerTransaction
    {
        return DB::transaction(function () use ($amount, $description, $saleId) {
            $this->increment('balance', $amount);

            return $this->transactions()->create([
                'sale_id' => $saleId,
                'type' => 'debit',
                'amount' => $amount,
                'transaction_date' => now(),
                'description' => $description,
            ]);
        });
    }

    /**
     * Add a credit (decreases what the customer owes, e.g. a payment).
     */
    public function addCredit(float $amount, string $description, ?int $saleId = null): CustomerTransaction
    {
        return DB::transaction(function () use ($amount, $description, $saleId) {
            $this->decrement('balance', $amount);

            return $this->transactions()->create([
                'sale_id' => $saleId,
                'type' => 'credit',
                'amount' => $amount,
                'transaction_date' => now(),
                'description' => $description,
            ]);
        });
    }
}
