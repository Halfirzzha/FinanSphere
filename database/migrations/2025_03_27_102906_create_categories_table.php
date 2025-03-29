<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name', 255)->unique()->comment('Category name, must be unique'); // Indexed and limited length
            $table->boolean('is_expense')->default(true)->comment('Indicates if the category is for expenses'); // Boolean flag
            $table->string('image')->nullable()->comment('Optional image path for the category'); // Nullable image path
            $table->timestamps(); // Created at and updated at timestamps

            // Add indexing for performance
            $table->index('name', 'idx_categories_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};