<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?string $navigationLabel = 'Users';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->description('Essential user identity and credentials')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->minLength(3)
                            ->maxLength(50)
                            ->alphaDash()
                            ->autocomplete(false)
                            ->placeholder('john_doe')
                            ->helperText('Unique username for login (letters, numbers, dashes, underscores)')
                            ->prefixIcon('heroicon-o-at-symbol'),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->autocomplete('email')
                            ->placeholder('user@example.com')
                            ->helperText('Valid email address')
                            ->prefixIcon('heroicon-o-envelope'),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn ($record) => $record === null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->revealable()
                            ->placeholder('Leave blank to keep current password')
                            ->helperText('Minimum 8 characters (only required for new users)')
                            ->prefixIcon('heroicon-o-lock-closed'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Personal Information')
                    ->description('User profile and contact details')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('name')
                            ->placeholder('John Doe')
                            ->helperText('Full name of the user')
                            ->prefixIcon('heroicon-o-user')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+62 812 3456 7890')
                            ->helperText('International format recommended')
                            ->prefixIcon('heroicon-o-phone')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Birth Date')
                            ->maxDate(now()->subYears(13))
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->placeholder('Select date')
                            ->helperText('Must be at least 13 years old')
                            ->prefixIcon('heroicon-o-cake')
                            ->columnSpan(1),

                        Forms\Components\FileUpload::make('avatar')
                            ->label('Profile Picture')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1'])
                            ->maxSize(2048)
                            ->directory('avatars')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Max 2MB. Supported: JPG, PNG, WebP')
                            ->columnSpan(2),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Account Status & Security')
                    ->description('Control account access and security settings')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Enable or disable user access'),

                        Forms\Components\Toggle::make('is_locked')
                            ->label('Account Locked')
                            ->default(false)
                            ->inline(false)
                            ->helperText('Lock account to prevent login'),

                        Forms\Components\Textarea::make('locked_reason')
                            ->label('Lock Reason')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Reason for locking this account...')
                            ->helperText('Explain why this account is locked')
                            ->visible(fn ($get) => $get('is_locked') === true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_login_count')
                    ->label('Logins')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked')
                    ->placeholder('All users')
                    ->trueLabel('Locked only')
                    ->falseLabel('Unlocked only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
