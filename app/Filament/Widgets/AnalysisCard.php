<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaction;
use App\Models\Debt;

class AnalysisCard extends BaseWidget
{
    use InteractsWithPageFilters;

    /**
     * Retrieve and format the statistics for the widget.
     *
     * @return array
     */
    protected function getStats(): array
    {
        [$startDate, $endDate] = $this->getDateRange();

        $revenue = $this->calculateTransactionSum(Transaction::incomes(), $startDate, $endDate);
        $expenses = $this->calculateTransactionSum(Transaction::expenses(), $startDate, $endDate);
        $difference = $revenue - $expenses;

        // Remaining debt (total debt - total paid)
        $remainingDebt = Debt::sum('amount') - Debt::sum('amount_paid');

        return [
            $this->createStat('Total Revenue', $revenue, 'heroicon-m-banknotes'),
            $this->createStat('Total Expenses', $expenses, 'heroicon-m-receipt-refund'),
            $this->createStat('Money Difference', $difference, 'heroicon-o-arrows-up-down'),
            $this->createStat('Remaining Debt', $remainingDebt, 'heroicon-o-exclamation-circle'),
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
     *
     * @param string $label
     * @param float $value
     * @param string $icon
     * @return \Filament\Widgets\StatsOverviewWidget\Stat
     */
    private function createStat(string $label, float $value, string $icon): Stat
    {
        return Stat::make($label, 'Rp ' . number_format($value, 0, ',', '.'))
            ->icon($icon);
    }
}