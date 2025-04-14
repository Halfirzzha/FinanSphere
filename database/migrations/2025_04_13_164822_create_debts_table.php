table.php
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
        Schema::create('debts', function (Blueprint $table) {
            // Primary identifier
            $table->id();
            
            // Debt name/description - limited to reasonable length
            $table->string('name', 100)->comment('Name or description of the debt');
            
            // Financial values with appropriate precision (12 digits total, 2 decimal places)
            $table->decimal('amount', 12, 2)->comment('Total debt amount')->unsigned();
            $table->decimal('amount_paid', 12, 2)->default(0.00)->comment('Amount already paid')->unsigned();
            $table->decimal('amount_remaining', 12, 2)->storedAs('amount - amount_paid')->comment('Remaining balance');
            
            // Date fields
            $table->date('start_date')->default(now())->comment('Date when debt was created');
            $table->date('maturity_date')->comment('Due date for debt payment');
            
            // Status with constraint to only allow valid values
            $table->enum('status', ['active', 'paid', 'defaulted', 'renegotiated'])
                  ->default('active')
                  ->comment('Current status of the debt');
            
            // Optional fields
            $table->string('note', 500)->nullable()->comment('Additional information about the debt');
            $table->decimal('interest_rate', 5, 2)->nullable()->comment('Annual interest rate in percentage')->unsigned();
            
            // Record management
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('status');
            $table->index('maturity_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};