<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Transaction extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'category_id',
        'date_transaction',
        'payment_method',
        'amount',
        'note',
        'image',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_transaction' => 'date',
        'amount' => 'integer',
    ];

    /**
     * Boot model events for cache invalidation.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache on create, update, delete
        static::created(fn() => static::clearTransactionCache());
        static::updated(fn() => static::clearTransactionCache());
        static::deleted(fn() => static::clearTransactionCache());
        static::restored(fn() => static::clearTransactionCache());
    }

    /**
     * Clear all transaction-related cache.
     */
    public static function clearTransactionCache(): void
    {
        Cache::tags(['transactions'])->flush();
    }

    /**
     * Get cached transaction statistics.
     */
    public static function getCachedStats(): array
    {
        return Cache::tags(['transactions'])->remember('transaction_stats', 3600, function () {
            return [
                'total_expenses' => static::expenses()->sum('amount'),
                'total_incomes' => static::incomes()->sum('amount'),
                'total_count' => static::count(),
                'expenses_count' => static::expenses()->count(),
                'incomes_count' => static::incomes()->count(),
            ];
        });
    }

    /**
     * Get the category associated with the transaction.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeExpenses($query)
    {
        return $query->whereHas('category', function ($query) {
            $query->where('is_expense', true);
        });
    }

    public function scopeIncomes($query)
    {
        return $query->whereHas('category', function ($query) {
            $query->where('is_expense', false);
        });
    }
}
