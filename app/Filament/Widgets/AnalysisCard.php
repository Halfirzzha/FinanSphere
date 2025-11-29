<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaction;
use App\Models\Debt;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class AnalysisCard extends BaseWidget
{
    use InteractsWithPageFilters, HasWidgetShield;

    /**
     * Retrieve and format the statistics for the widget.
     * OPTIMIZED: Single query for all calculations, added caching
     *
     * @return array
     */
    protected function getStats(): array
    {
        [$startDate, $endDate] = $this->getDateRange();

        // PERFORMANCE: Cache widget data for 5 minutes
        $cacheKey = 'widget_analysis_' . md5(($startDate ?? '') . $endDate);

        $stats = cache()->remember($cacheKey, 300, function () use ($startDate, $endDate) {
            // OPTIMIZATION: Single query with conditional aggregation
            $transactionStats = DB::table('transactions')
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->when($startDate, fn($q) => $q->where('date_transaction', '>=', $startDate))
                ->where('date_transaction', '<=', $endDate)
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN categories.is_expense = 0 THEN transactions.amount ELSE 0 END), 0) as revenue,
                    COALESCE(SUM(CASE WHEN categories.is_expense = 1 THEN transactions.amount ELSE 0 END), 0) as expenses
                ')
                ->first();

            // OPTIMIZATION: Single query for debt
            $debtStats = Debt::selectRaw('
                COALESCE(SUM(amount), 0) as total_debt,
                COALESCE(SUM(amount_paid), 0) as total_paid
            ')->first();

            return [
                'revenue' => (float) $transactionStats->revenue,
                'expenses' => (float) $transactionStats->expenses,
                'remaining_debt' => (float) ($debtStats->total_debt - $debtStats->total_paid),
            ];
        });

        $revenue = $stats['revenue'];
        $expenses = $stats['expenses'];
        $difference = $revenue - $expenses;
        $remainingDebt = $stats['remaining_debt'];

        return [
            $this->createStat('Total Revenue', $revenue, 'heroicon-m-banknotes', 'success'),
            $this->createStat('Total Expenses', $expenses, 'heroicon-m-receipt-refund', 'danger'),
            $this->createStat('Money Difference', $difference, 'heroicon-o-arrows-up-down', $difference >= 0 ? 'success' : 'danger'),
            $this->createStat('Remaining Debt', $remainingDebt, 'heroicon-o-exclamation-circle', $remainingDebt > 0 ? 'warning' : 'success'),
        ];
    }

    /**
     * Get the date range from filters or defaults.
     *
     * @return array
     */
    private function getDateRange(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? now();

        return [
            $startDate ? Carbon::parse($startDate) : null,
            Carbon::parse($endDate),
        ];
    }

    /**
     * Calculate the sum of transactions within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Support\Carbon|null $startDate
     * @param \Illuminate\Support\Carbon $endDate
     * @return float
     */
    private function calculateTransactionSum($query, ?Carbon $startDate, Carbon $endDate): float
    {
        return $query
            ->when($startDate, fn($q) => $q->where('date_transaction', '>=', $startDate))
            ->where('date_transaction', '<=', $endDate)
            ->sum('amount');
    }

    /**
     * Create a formatted Stat object.
     * ENHANCED: Added color support
     *
     * @param string $label
     * @param float $value
     * @param string $icon
     * @param string|null $color
     * @return \Filament\Widgets\StatsOverviewWidget\Stat
     */
    private function createStat(string $label, float $value, string $icon, ?string $color = null): Stat
    {
        $stat = Stat::make($label, 'Rp ' . number_format($value, 0, ',', '.'))
            ->icon($icon);

        if ($color) {
            $stat->color($color);
        }

        return $stat;
    }
}
