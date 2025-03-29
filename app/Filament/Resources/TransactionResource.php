<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use function Laravel\Prompts\select;
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
    protected static ?string $navigationGroup = 'Finance Management'; // Group name
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Section::make('Transaction Details')
                ->columns([
                    'sm' => 3,
                    'xl' => 6,
                    '2xl' => 8,
                ])
                ->schema([
                    TextInput::make('code')
                        ->label('Transaction Code')
                        ->required()
                        ->default(fn (): string => sprintf('TRX - %s', strtoupper(Str::random(8))))
                        ->readOnly()
                        ->maxLength(50)
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 2,
                        ]),
                    TextInput::make('name')
                        ->label('Transaction Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 4,
                        ]),
                    Select::make('category_id')
                        ->label('Category ID')
                        ->relationship('category', 'name')
                        ->options(function () {
                            return \App\Models\Category::query()
                                ->orderByRaw("FIELD(is_expense, 0, 1)")
                                ->get()
                                ->mapWithKeys(function ($category) {
                                    $type = $category->is_expense ? '( Pengeluaran )' : '( Pemasukan )';
                                    return [$category->id => "$category->name - $type"];
                                });
                        })
                        ->required()
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 1,
                        ]),
                    DatePicker::make('date_transaction')
                        ->label('Transaction Date')
                        ->required()
                        ->columnSpan(1),
                    select::make('payment_method')
                        ->label('Payment Method')
                        ->required()
                        ->options([
                            'cash' => 'Cash',
                            'credit_card' => 'Credit Card',
                            'bank_transfer' => 'Bank Transfer',
                            'digital_wallet' => 'Digital Wallet',
                        ])
                        ->columnSpan(1),
                    TextInput::make('amount')
                        ->label('Amount')
                        ->required()
                        ->prefix('Rp')
                        ->numeric()
                        ->columnSpan(2),
                    RichEditor::make('note')
                        ->label('Note')
                        ->maxLength(500)
                        ->default(null)
                        ->columnSpanFull(),
                    FileUpload::make('image')
                        ->label('Transaction Image')
                        ->image()
                        ->directory('transaction-images')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->label('Code Of Transaction')
                    ->searchable(),
                ImageColumn::make('category.image')
                    ->label('Photo Category')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->description(fn (Transaction $record): string => $record->name)
                    ->label('Transaksi'),

                Tables\Columns\IconColumn::make('category.is_expense')
                    ->label('Active')
                    ->trueIcon('heroicon-m-receipt-refund')
                    ->falseIcon('heroicon-m-banknotes')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->boolean(),
                    
                TextColumn::make('date_transaction')
                    ->label('Date Transaction')
                    ->date()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucfirst($state))),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => 'Rp ' . number_format((float) str_replace(['Rp', '.', ','], ['', '', '.'], $state), 2, ',', '.')),

                TextColumn::make('note')
                    ->label('Note')
                    ->html()
                    ->searchable(),

                ImageColumn::make('image')
                    ->label('Proof Image'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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