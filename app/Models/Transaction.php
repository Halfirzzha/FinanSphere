<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
     * Validation rules for transactions
     */
    public static function validationRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:transactions,code'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'date_transaction' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', 'in:cash,credit_card,bank_transfer,digital_wallet'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'note' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }

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
     * FIXED: Handle cache drivers that don't support tags (file, database)
     */
    public static function clearTransactionCache(): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::tags(['transactions'])->flush();
            } else {
                // Fallback for drivers without tag support
                Cache::forget('transaction_stats');
                Cache::forget('total_expenses');
                Cache::forget('total_incomes');
                Cache::forget('recent_transactions');
            }
        } catch (\Exception $e) {
            Log::warning('Cache clear failed', ['error' => $e->getMessage()]);
            // Continue execution even if cache clear fails
        }
    }

    /**
     * Get cached transaction statistics.
     * OPTIMIZED: Added query optimization and cache driver handling
     */
    public static function getCachedStats(): array
    {
        $cacheKey = 'transaction_stats';
        $cacheDuration = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheDuration, function () {
            // PERFORMANCE: Use single query with conditional aggregation
            $stats = static::selectRaw('
                COALESCE(SUM(CASE WHEN categories.is_expense = 1 THEN transactions.amount ELSE 0 END), 0) as total_expenses,
                COALESCE(SUM(CASE WHEN categories.is_expense = 0 THEN transactions.amount ELSE 0 END), 0) as total_incomes,
                COUNT(*) as total_count,
                SUM(CASE WHEN categories.is_expense = 1 THEN 1 ELSE 0 END) as expenses_count,
                SUM(CASE WHEN categories.is_expense = 0 THEN 1 ELSE 0 END) as incomes_count
            ')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->first();

            return [
                'total_expenses' => (float) $stats->total_expenses,
                'total_incomes' => (float) $stats->total_incomes,
                'total_count' => (int) $stats->total_count,
                'expenses_count' => (int) $stats->expenses_count,
                'incomes_count' => (int) $stats->incomes_count,
                'balance' => (float) ($stats->total_incomes - $stats->total_expenses),
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

    /**
     * OPTIMIZED: Scope for expense transactions using whereHas for Trend compatibility
     * Note: Using whereHas instead of JOIN to avoid column ambiguity with Trend library
     * that relies on created_at for date grouping
     */
    public function scopeExpenses($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('is_expense', true);
        });
    }

    /**
     * OPTIMIZED: Scope for income transactions using whereHas for Trend compatibility
     * Note: Using whereHas instead of JOIN to avoid column ambiguity with Trend library
     * that relies on created_at for date grouping
     */
    public function scopeIncomes($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('is_expense', false);
        });
    }

    /**
     * Scope for transactions in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_transaction', [$startDate, $endDate]);
    }

    /**
     * Scope for recent transactions (optimized with limit)
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('date_transaction', 'desc')
                     ->orderBy('created_at', 'desc')
                     ->limit($limit);
    }
}
