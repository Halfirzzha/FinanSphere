<?php

namespace App\Filament\Widgets;

use Flowframe\Trend\Trend;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;

class WidgetExpenseChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Expenses Chart';
    protected static ?int $sort = 2;

    /**
     * Get the chart data to be displayed.
     *
     * @return array
     */
    protected function getData(): array
    {
        // Validate and parse filters with fallback to defaults
        $startDate = $this->validateDate($this->filters['startDate'] ?? null, Carbon::now()->subMonth());
        $endDate = $this->validateDate($this->filters['endDate'] ?? null, now());

        // Cache key for the query
        $cacheKey = "expenses_chart_{$startDate->toDateString()}_{$endDate->toDateString()}";

        // Retrieve data from cache or execute query
        $data = Cache::remember($cacheKey, now()->addMinutes(1), function () use ($startDate, $endDate) {
            return Trend::query(Transaction::expenses())
                ->between($startDate, $endDate)
                ->perDay()
                ->sum('amount');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Daily Expenses',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    /**
     * Get the type of chart to render.
     *
     * @return string
     */
    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Validate and parse a date string, or return a default value.
     *
     * @param string|null $date
     * @param Carbon $default
     * @return Carbon
     */
    private function validateDate(?string $date, Carbon $default): Carbon
    {
        try {
            return $date ? Carbon::parse($date) : $default;
        } catch (\Exception $e) {
            // Log invalid date input for debugging
            logger()->warning("Invalid date input: {$date}", ['exception' => $e]);
            return $default;
        }
    }
}