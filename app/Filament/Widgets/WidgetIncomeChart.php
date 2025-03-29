<?php

namespace App\Filament\Widgets;

use Flowframe\Trend\Trend;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class WidgetIncomeChart extends ChartWidget
{
    use InteractsWithPageFilters;

    /**
     * Widget heading.
     */
    protected static ?string $heading = 'Revenue Chart';

    /**
     * Widget sort order.
     */
    protected static ?int $sort = 1;

    /**
     * Get the chart data.
     *
     * @return array
     */
    protected function getData(): array
    {
        // Validate and parse date filters
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        // Fetch income data using Trend
        $data = Trend::query(Transaction::incomes())
            ->between($startDate, $endDate)
            ->perDay()
            ->sum('amount');

        // Return formatted chart data
        return [
            'datasets' => [
                [
                    'label' => 'Daily Incomes',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    /**
     * Get the chart type.
     *
     * @return string
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Get the start date from filters or default to one month ago.
     *
     * @return Carbon
     */
    private function getStartDate(): Carbon
    {
        return isset($this->filters['startDate']) && !empty($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])
            : Carbon::now()->subMonth();
    }

    /**
     * Get the end date from filters or default to now.
     *
     * @return Carbon
     */
    private function getEndDate(): Carbon
    {
        return isset($this->filters['endDate']) && !empty($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])
            : Carbon::now();
    }
}