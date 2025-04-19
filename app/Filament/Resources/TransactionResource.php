<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Transaction;
use App\Models\Category;
// Removed unused import
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransactionResource\Pages;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationGroup = 'Finance Management';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    /**
     * Define form fields for transaction creation and editing
     * 
     * @param Forms\Form $form
     * @return Forms\Form
     */
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Section::make('Transaction Details')
                ->description('Record financial transaction details')
                ->columns([
                    'sm' => 3,
                    'xl' => 6,
                    '2xl' => 8,
                ])
                ->schema([
                    TextInput::make('code')
                        ->label('Transaction Code')
                        ->required()
                        ->default(function (): string {
                            $millis = round(microtime(true) * 1000);
                            $uniqueId = base_convert(substr($millis, -6) . rand(100, 999), 10, 36);
                            return "FNTX - " . strtoupper($uniqueId);
                        })
                        ->readOnly()
                        ->maxLength(50)
                        ->unique(ignorable: fn ($record) => $record)
                        ->helperText('Unique transaction identifier')
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 2,
                        ]),
                    TextInput::make('name')
                        ->label('Description')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Brief description of this transaction')
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 4,
                        ]),
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->preload()
                        ->searchable()
                        ->options(function () {
                            return Category::query()
                                ->orderByRaw("FIELD(is_expense, 0, 1)")
                                ->get()
                                ->mapWithKeys(function ($category) {
                                    $type = $category->is_expense ? '(Expense)' : '(Income)';
                                    return [$category->id => "$category->name $type"];
                                });
                        })
                        ->required()
                        ->helperText('Select transaction category')
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 1,
                        ]),
                    DatePicker::make('date_transaction')
                        ->label('Transaction Date')
                        ->required()
                        ->default(now())
                        ->helperText('When the transaction occurred')
                        ->columnSpan(1),
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->required()
                        ->options([
                            'cash' => 'Cash',
                            'credit_card' => 'Credit Card',
                            'bank_transfer' => 'Bank Transfer',
                            'digital_wallet' => 'Digital Wallet',
                        ])
                        ->helperText('Method used for this transaction')
                        ->columnSpan(1),
                    TextInput::make('amount')
                        ->label('Amount')
                        ->required()
                        ->prefix('Rp')
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->helperText('Transaction amount (in IDR)')
                        ->columnSpan(2),
                    RichEditor::make('note')
                        ->label('Notes')
                        ->maxLength(500)
                        ->helperText('Additional details about this transaction (max 500 chars)')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'bulletList', 
                            'orderedList', 'redo', 'undo'
                        ])
                        ->columnSpanFull(),
                    FileUpload::make('image')
                        ->label('Receipt/Proof')
                        ->image()
                        ->directory('transaction-receipts')
                        ->maxSize(2048) // 2MB limit
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'application/pdf'])
                        ->helperText('Upload transaction receipt or proof (max 2MB)')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * Define table columns, filters, and actions
     * 
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->filters(self::getTableFilters())
            ->actions(self::getTableActions())
            ->bulkActions(self::getTableBulkActions())
            ->defaultSort('date_transaction', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('code')
                ->label('Transaction ID')
                ->searchable()
                ->copyable()
                ->tooltip('Unique transaction identifier'),
            ImageColumn::make('category.image')
                ->label('Category')
                ->circular()
                ->defaultImageUrl(fn (Transaction $record) => 
                    $record->category->is_expense 
                        ? asset('images/expense-default.png')
                        : asset('images/income-default.png')
                ),
            TextColumn::make('category.name')
                ->description(fn (Transaction $record): string => $record->name)
                ->label('Transaction')
                ->searchable(['transactions.name', 'categories.name'])
                ->sortable(),
            Tables\Columns\IconColumn::make('category.is_expense')
                ->label('Type')
                ->trueIcon('heroicon-m-receipt-refund')
                ->falseIcon('heroicon-m-banknotes')
                ->trueColor('danger')
                ->falseColor('success')
                ->boolean()
                ->tooltip(fn (Transaction $record): string => 
                    $record->category->is_expense ? 'Expense' : 'Income'
                ),
            TextColumn::make('date_transaction')
                ->label('Date')
                ->date('d M Y')
                ->sortable(),
            TextColumn::make('payment_method')
                ->label('Payment Method')
                ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state)))
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'cash' => 'success',
                    'credit_card' => 'warning',
                    'bank_transfer' => 'info',
                    'digital_wallet' => 'primary',
                    default => 'gray',
                }),
            TextColumn::make('amount')
                ->label('Amount')
                ->money('IDR')
                ->sortable()
                ->alignRight()
                ->color(fn (Transaction $record): string => 
                    $record->category->is_expense ? 'danger' : 'success'
                ),
            TextColumn::make('note')
                ->label('Notes')
                ->html()
                ->limit(50)
                ->tooltip(function (Transaction $record): ?string {
                    if (strlen(strip_tags($record->note)) > 50) {
                        return $record->note;
                    }
                    return null;
                })
                ->searchable(),
            ImageColumn::make('image')
                ->label('Receipt')
                ->square()
                ->toggleable(),
            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label('Updated')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('deleted_at')
                ->label('Deleted')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\TrashedFilter::make()
                ->label('Show Deleted Transactions'),
            Tables\Filters\SelectFilter::make('category_type')
                ->label('Transaction Type')
                ->options([
                    'income' => 'Income Only',
                    'expense' => 'Expenses Only',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when($data['value'] === 'income', function (Builder $query) {
                        return $query->whereHas('category', fn (Builder $query) => 
                            $query->where('is_expense', false)
                        );
                    })->when($data['value'] === 'expense', function (Builder $query) {
                        return $query->whereHas('category', fn (Builder $query) => 
                            $query->where('is_expense', true)
                        );
                    });
                }),
            Tables\Filters\SelectFilter::make('payment_method')
                ->options([
                    'cash' => 'Cash',
                    'credit_card' => 'Credit Card',
                    'bank_transfer' => 'Bank Transfer',
                    'digital_wallet' => 'Digital Wallet',
                ]),
            Tables\Filters\Filter::make('date_range')
                ->form([
                    Forms\Components\DatePicker::make('from'),
                    Forms\Components\DatePicker::make('until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'], 
                            fn (Builder $query, $date): Builder => 
                                $query->whereDate('date_transaction', '>=', $date)
                        )
                        ->when(
                            $data['until'], 
                            fn (Builder $query, $date): Builder => 
                                $query->whereDate('date_transaction', '<=', $date)
                        );
                }),
        ];
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\RestoreBulkAction::make(),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // Define relations here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}