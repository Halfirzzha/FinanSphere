<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationGroup = 'Finance Management'; // Group name
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Section::make()
                ->columns([
                    'sm' => 3,
                    'xl' => 6,
                    '2xl' => 8,
                ])
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 2,
                            '2xl' => 2,
                        ]),
                    Toggle::make('is_expense')
                        ->label('Active')
                        ->required()
                        ->onIcon('heroicon-m-receipt-refund')
                        ->offIcon('heroicon-m-banknotes')
                        ->onColor('danger')
                        ->offColor('success')
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 1,
                        ])
                        ->inline(false),
                    FileUpload::make('image')
                        ->image()
                        ->directory('images-categories')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('PHOTO'),
                TextColumn::make('name')
                    ->label('Name of the category')
                    ->searchable(),
                IconColumn::make('is_expense')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-m-receipt-refund')
                    ->falseIcon('heroicon-m-banknotes')
                    ->trueColor('danger')
                    ->falseColor('success'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add filters here if needed
            ])
            ->actions([
                EditAction::make()
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}