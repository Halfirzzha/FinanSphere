<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;

class FilterDate extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    /**
     * Define the filters form schema.
     *
     * @param Form $form
     * @return Form
     */
    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Filter Dates') // Add a meaningful section title
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->maxDate(fn (Get $get) => $get('endDate') ?: now())
                        ->required() // Ensure the field is required
                        ->placeholder('Select a start date'), // Add a placeholder for better UX
                        
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->minDate(fn (Get $get) => $get('startDate') ?: now())
                        ->maxDate(now())
                        ->required() // Ensure the field is required
                        ->placeholder('Select an end date'), // Add a placeholder for better UX
                ])
                ->columns(2), // Maintain a clean layout with two columns
        ]);
    }
}