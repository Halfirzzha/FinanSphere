<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL FIX: Add user_id foreign keys for row-level security
     * OPTIMIZATION: Add composite indexes for query performance
     */
    public function up(): void
    {
        // Add user_id to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Owner of this transaction');

            // Composite indexes for performance (user + date queries)
            $table->index(['user_id', 'date_transaction'], 'idx_transactions_user_date');
            $table->index(['user_id', 'category_id'], 'idx_transactions_user_category');
        });

        // Add user_id to debts table
        Schema::table('debts', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Owner of this debt');

            // Composite indexes for performance
            $table->index(['user_id', 'status'], 'idx_debts_user_status');
            $table->index(['user_id', 'maturity_date'], 'idx_debts_user_maturity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_user_date');
            $table->dropIndex('idx_transactions_user_category');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex('idx_debts_user_status');
            $table->dropIndex('idx_debts_user_maturity');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
