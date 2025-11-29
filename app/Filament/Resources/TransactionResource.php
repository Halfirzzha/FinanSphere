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
            Forms\Components\Tabs::make('Transaction Information')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Transaction Info')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            TextInput::make('code')
                                ->label('Transaction Code')
                                ->required()
                                ->default(function (): string {
                                    $millis = round(microtime(true) * 1000);
                                    $uniqueId = base_convert(substr($millis, -6) . rand(100, 999), 10, 36);
                                    return "FNTX-" . strtoupper($uniqueId);
                                })
                                ->readOnly()
                                ->maxLength(50)
                                ->unique(ignorable: fn ($record) => $record)
                                ->helperText('Auto-generated unique code')
                                ->prefixIcon('heroicon-o-hashtag')
                                ->columnSpan(1),

                            TextInput::make('name')
                                ->label('Description')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Monthly Grocery Shopping')
                                ->helperText('Brief transaction description')
                                ->prefixIcon('heroicon-o-document-text')
                                ->columnSpan(2),

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
                                            $type = $category->is_expense ? '💸 Expense' : '💰 Income';
                                            return [$category->id => "$category->name ($type)"];
                                        });
                                })
                                ->required()
                                ->helperText('Select income or expense category')
                                ->prefixIcon('heroicon-o-folder')
                                ->columnSpan(2),

                            DatePicker::make('date_transaction')
                                ->label('Transaction Date')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->maxDate(now())
                                ->helperText('When this transaction occurred')
                                ->prefixIcon('heroicon-o-calendar')
                                ->columnSpan(1),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->required()
                                ->options([
                                    'cash' => '💵 Cash',
                                    'credit_card' => '💳 Credit Card',
                                    'bank_transfer' => '🏦 Bank Transfer',
                                    'digital_wallet' => '📱 Digital Wallet',
                                ])
                                ->helperText('How this was paid')
                                ->prefixIcon('heroicon-o-credit-card')
                                ->columnSpan(1),
                        ])
                        ->columns(3),

                    Forms\Components\Tabs\Tab::make('Financial Details')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            TextInput::make('amount')
                                ->label('Transaction Amount')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(999999999999)
                                ->step(1)
                                ->inputMode('numeric')
                                ->prefix('Rp')
                                ->placeholder('50000')
                                ->helperText('Enter amount in Rupiah (numbers only)')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('Additional Info')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            RichEditor::make('note')
                                ->label('Transaction Notes')
                                ->maxLength(500)
                                ->placeholder('Add any additional details...')
                                ->helperText('Optional notes about this transaction (max 500 characters)')
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'bulletList',
                                    'orderedList', 'redo', 'undo'
                                ])
                                ->columnSpanFull(),

                            FileUpload::make('image')
                                ->label('Receipt/Proof')
                                ->image()
                                ->imageEditor()
                                ->directory('transaction-receipts')
                                ->visibility('public')
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                ->helperText('Upload receipt or proof (Max 2MB, JPG/PNG/WebP)')
                                ->imagePreviewHeight(200)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
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
