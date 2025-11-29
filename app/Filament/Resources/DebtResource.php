<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtResource\Pages;
use App\Models\Debt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class DebtResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Finance Management'; // Group name
    protected static ?string $navigationLabel = 'Payables & Loans'; // Navigation label

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Debt Management')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Debt Information')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Debt Name')
                                    ->required()
                                    ->placeholder('e.g., Bank BCA Business Loan')
                                    ->maxLength(100)
                                    ->helperText('Descriptive name for this debt')
                                    ->prefixIcon('heroicon-o-identification')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Financial Details')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Total Debt Amount')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(999999999999)
                                    ->step(1)
                                    ->inputMode('numeric')
                                    ->prefix('Rp')
                                    ->placeholder('10000000')
                                    ->helperText('Original debt amount (numbers only)')
                                    ->rule('regex:/^[0-9]+$/') // SECURITY: Only integers
                                    ->dehydrateStateUsing(fn ($state) => abs((int) $state))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('amount_paid')
                                    ->label('Amount Paid')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(999999999999)
                                    ->step(1)
                                    ->inputMode('numeric')
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->placeholder('2000000')
                                    ->helperText('Total already paid (numbers only)')
                                    ->rule('regex:/^[0-9]+$/') // SECURITY: Only integers
                                    ->dehydrateStateUsing(fn ($state) => abs((int) $state))
                                    ->rule(function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            $amount = request()->input('amount');
                                            if ($amount && $value > $amount) {
                                                $fail('Amount paid cannot exceed total debt amount.');
                                            }
                                        };
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('interest_rate')
                                    ->label('Interest Rate')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->inputMode('decimal')
                                    ->suffix('%')
                                    ->placeholder('5.5')
                                    ->helperText('Annual interest rate (optional)')
                                    ->columnSpan(1),
                            ])
                            ->columns(3),

                        Forms\Components\Tabs\Tab::make('Timeline & Status')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->maxDate(now())
                                    ->helperText('When this debt started')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('maturity_date')
                                    ->label('Maturity Date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->minDate(now())
                                    ->helperText('When this debt is due')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status')
                                    ->label('Debt Status')
                                    ->options([
                                        Debt::STATUS_ACTIVE => '🟢 Active',
                                        Debt::STATUS_PAID => '✅ Paid Off',
                                        Debt::STATUS_DEFAULTED => '❌ Defaulted',
                                        Debt::STATUS_RENEGOTIATED => '🔄 Renegotiated',
                                    ])
                                    ->default(Debt::STATUS_ACTIVE)
                                    ->required()
                                    ->helperText('Current payment status')
                                    ->prefixIcon('heroicon-o-flag')
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('note')
                                    ->label('Additional Notes')
                                    ->placeholder('Add any additional details about this debt...')
                                    ->maxLength(500)
                                    ->helperText('Optional notes (max 500 characters)')
                                    ->rows(3)
                                    ->dehydrateStateUsing(fn ($state) => $state ? e($state) : null) // XSS protection
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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

                Tables\Columns\TextColumn::make('interest_rate')
                    ->label('Bunga')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebts::route('/'),
            'create' => Pages\CreateDebt::route('/create'),
            'edit' => Pages\EditDebt::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'debts.*',
                // OPTIMIZATION: Pre-calculate amount_remaining in query
                DB::raw('(amount - amount_paid) as amount_remaining')
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Get navigation badge - show count of active debts
     */
    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('active_debts_count', 300, function () {
            return (string) Debt::where('status', Debt::STATUS_ACTIVE)->count();
        });
    }

    /**
     * Get navigation badge color based on overdue status
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $hasOverdue = Debt::active()->where('maturity_date', '<', now())->exists();
        return $hasOverdue ? 'danger' : 'success';
    }
}
