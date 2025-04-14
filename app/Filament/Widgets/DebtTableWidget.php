<?php

namespace App\Filament\Widgets;

use App\Models\Debt;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DebtTableWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Debt::query()->active()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount_remaining')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('paymentPercentage')
                    ->label('Progres')
                    ->formatStateUsing(fn (string $state): string => "{$state}%")
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('interest_rate')
                ->label('Bunga')
                ->colors([
                        'primary' => fn (string $state): bool => $state > 0,
                        'secondary' => fn (string $state): bool => $state == 0,
                    ])
                    ->formatStateUsing(fn (string $state): string => "{$state}%")
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'danger' => Debt::STATUS_DEFAULTED,
                        'warning' => Debt::STATUS_RENEGOTIATED,
                        'success' => Debt::STATUS_PAID,
                        'primary' => Debt::STATUS_ACTIVE,
                    ]),
                    
                Tables\Columns\TextColumn::make('maturity_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('isOverdue')
                    ->label('Terlambat')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Debt::STATUS_ACTIVE => 'Aktif',
                        Debt::STATUS_PAID => 'Lunas',
                        Debt::STATUS_DEFAULTED => 'Gagal Bayar',
                        Debt::STATUS_RENEGOTIATED => 'Renegosiasi',
                    ]),
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('Terlambat')
                    ->query(fn (Builder $query) => $query->where('status', Debt::STATUS_ACTIVE)
                                                         ->where('maturity_date', '<', now())),
            ])
            ->paginated([10, 25, 50]);
    }
}