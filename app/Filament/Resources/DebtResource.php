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
                Section::make('Informasi Utama')
                    ->description('Informasi dasar tentang hutang/pinjaman')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Hutang')
                            ->required()
                            ->placeholder('Contoh: Pinjaman Bank BCA')
                            ->maxLength(100)
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                            ]),
                    ])
                    ->columns([
                        'sm' => 2,
                        'xl' => 2,
                    ]),
                    
                Section::make('Detail Keuangan')
                    ->description('Informasi jumlah dan pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Total Hutang')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->columnSpan([
                                'sm' => 1,
                                'xl' => 1, 
                            ]),
                            
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Sudah Dibayar')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('Rp')
                            ->columnSpan([
                                'sm' => 1,
                                'xl' => 1,
                            ]),
                            
                        Forms\Components\TextInput::make('interest_rate')
                            ->label('Suku Bunga (%)')
                            ->numeric()
                            ->suffix('%')
                            ->placeholder('0.00')
                            ->columnSpan([
                                'sm' => 1,
                                'xl' => 1,
                            ]),
                    ])
                    ->columns([
                        'sm' => 1,
                        'xl' => 3,
                    ]),
                    
                Section::make('Tanggal')
                    ->description('Informasi waktu terkait hutang/pinjaman')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now())
                            ->columnSpan([
                                'sm' => 1,
                                'xl' => 1,
                            ]),
                            
                        Forms\Components\DatePicker::make('maturity_date')
                            ->label('Tanggal Jatuh Tempo')
                            ->required()
                            ->columnSpan([
                                'sm' => 1,
                                'xl' => 1,
                            ]),
                    ])
                    ->columns([
                        'sm' => 1,
                        'xl' => 2,
                    ]),
                    
                Section::make('Status dan Catatan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                Debt::STATUS_ACTIVE => 'Aktif',
                                Debt::STATUS_PAID => 'Lunas',
                                Debt::STATUS_DEFAULTED => 'Gagal Bayar',
                                Debt::STATUS_RENEGOTIATED => 'Renegosiasi',
                            ])
                            ->default(Debt::STATUS_ACTIVE)
                            ->required()
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 1,
                            ]),
                            
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->placeholder('Informasi tambahan mengenai hutang/pinjaman ini')
                            ->maxLength(500)
                            ->columnSpanfull(),
                    ])
                    ->columns([
                        'sm' => 2,
                        'xl' => 2,
                    ]),
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
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
