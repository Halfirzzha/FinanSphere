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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationGroup = 'Finance Management'; // Group name
    protected static ?string $navigationIcon = 'heroicon-o-folder';

    /**
     * Shield: Control navigation visibility based on permission
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /**
     * Shield: Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->can('view_any_category');
    }

    /**
     * Shield: Check if user can create records
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->can('create_category');
    }

    /**
     * Shield: Check if user can edit specific record
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        return $user && $user->can('update_category');
    }

    /**
     * Shield: Check if user can delete specific record
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();
        return $user && $user->can('delete_category');
    }

    /**
     * Shield: Check if user can delete any records
     */
    public static function canDeleteAny(): bool
    {
        $user = Auth::user();
        return $user && $user->can('delete_any_category');
    }

    /**
     * Define form fields for category creation and editing
     *
     * @param Forms\Form $form
     * @return Forms\Form
     */
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Category Details')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Basic Information')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            TextInput::make('name')
                                ->label('Category Name')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->placeholder('e.g., Food & Beverage, Salary, Entertainment')
                                ->helperText('Descriptive name for this category')
                                ->prefixIcon('heroicon-o-bookmark')
                                ->columnSpan(2),

                            Toggle::make('is_expense')
                                ->label('Expense Category')
                                ->required()
                                ->default(true)
                                ->onIcon('heroicon-m-minus-circle')
                                ->offIcon('heroicon-m-plus-circle')
                                ->onColor('danger')
                                ->offColor('success')
                                ->inline(false)
                                ->helperText('Toggle ON for expenses, OFF for income')
                                ->columnSpan(1),
                        ])
                        ->columns(3),

                    Forms\Components\Tabs\Tab::make('Category Icon')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            FileUpload::make('image')
                                ->label('Category Icon')
                                ->image()
                                ->imageEditor()
                                ->imageEditorAspectRatios(['1:1'])
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth(200)
                                ->imageResizeTargetHeight(200)
                                ->directory('categories')
                                ->visibility('public')
                                ->maxSize(1024)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                                ->helperText('Square icon recommended. Will be resized to 200x200px (Max 1MB)')
                                ->imagePreviewHeight(150)
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
            ->columns([
                ImageColumn::make('image')
                    ->label('Icon')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_expense')
                    ->label('Type')
                    ->boolean()
                    ->trueIcon('heroicon-m-receipt-refund')
                    ->falseIcon('heroicon-m-banknotes')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->tooltip(fn (Category $record): string =>
                        $record->is_expense ? 'Expense Category' : 'Income Category'
                    ),
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
                Tables\Filters\SelectFilter::make('is_expense')
                    ->label('Category Type')
                    ->options([
                        '1' => 'Expense Categories',
                        '0' => 'Income Categories',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Tables\Filters\Filter $filter, $query) {
                        return $query
                            ->when(
                                $filter->getState()['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $filter->getState()['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    })
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
