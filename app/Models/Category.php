<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    // Example of a future relationship
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}