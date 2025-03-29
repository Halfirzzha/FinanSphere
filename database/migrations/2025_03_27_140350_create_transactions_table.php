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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('code', 50)->unique()->comment('Unique transaction code'); // Limited length for security
            $table->string('name', 255)->comment('Transaction name'); // Limited length
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete()
                ->comment('Foreign key to categories table'); // Foreign key with cascade delete
            $table->date('date_transaction')->index()->comment('Transaction date'); // Indexed for performance
            $table->enum('payment_method', ['cash', 'credit_card', 'bank_transfer', 'digital_wallet'])
                ->comment('Payment method used for the transaction'); // Enum for predefined values
            $table->unsignedBigInteger('amount')->comment('Transaction amount, must be positive'); // Unsigned for positive values
            $table->string('note', 500)->nullable()->comment('Optional transaction note'); // Nullable and limited length
            $table->string('image')->nullable()->comment('Optional image path for the transaction'); // Nullable image path
            $table->timestamps(); // Created at and updated at timestamps
            $table->softDeletes()->comment('Soft delete column'); // Soft delete support
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};