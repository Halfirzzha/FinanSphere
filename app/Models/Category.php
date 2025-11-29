<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Category extends Model
{
    protected $fillable = [
        'name',
        'is_expense',
        'image',
    ];

    protected $casts = [
        'is_expense' => 'boolean',
    ];

    /**
     * Validation rules for categories
     */
    public static function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'is_expense' => ['required', 'boolean'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Boot model events for cache invalidation.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache on create, update, delete
        static::created(fn() => static::clearCategoryCache());
        static::updated(fn() => static::clearCategoryCache());
        static::deleted(fn() => static::clearCategoryCache());
    }

    /**
     * Clear all category-related cache.
     * FIXED: Handle cache drivers without tag support
     */
    public static function clearCategoryCache(): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::tags(['categories'])->flush();
            } else {
                Cache::forget('all_categories');
                Cache::forget('expense_categories');
                Cache::forget('income_categories');
            }
        } catch (\Exception $e) {
            Log::warning('Category cache clear failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all categories with caching.
     * FIXED: Removed tag dependency for compatibility
     */
    public static function getCached()
    {
        return Cache::remember('all_categories', 3600, function () {
            return static::orderBy('name')->get();
        });
    }

    /**
     * Get expense categories with caching.
     * FIXED: Removed tag dependency
     */
    public static function getExpenseCategories()
    {
        return Cache::remember('expense_categories', 3600, function () {
            return static::where('is_expense', true)->orderBy('name')->get();
        });
    }

    /**
     * Get income categories with caching.
     * FIXED: Removed tag dependency
     */
    public static function getIncomeCategories()
    {
        return Cache::remember('income_categories', 3600, function () {
            return static::where('is_expense', false)->orderBy('name')->get();
        });
    }

    // Example of a future relationship
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
