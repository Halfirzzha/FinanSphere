<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
     */
    public static function clearCategoryCache(): void
    {
        Cache::tags(['categories'])->flush();
    }

    /**
     * Get all categories with caching.
     */
    public static function getCached()
    {
        return Cache::tags(['categories'])->remember('all_categories', 3600, function () {
            return static::all();
        });
    }

    /**
     * Get expense categories with caching.
     */
    public static function getExpenseCategories()
    {
        return Cache::tags(['categories'])->remember('expense_categories', 3600, function () {
            return static::where('is_expense', true)->get();
        });
    }

    /**
     * Get income categories with caching.
     */
    public static function getIncomeCategories()
    {
        return Cache::tags(['categories'])->remember('income_categories', 3600, function () {
            return static::where('is_expense', false)->get();
        });
    }

    // Example of a future relationship
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
