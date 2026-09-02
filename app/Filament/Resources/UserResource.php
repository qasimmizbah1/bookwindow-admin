<?php

// app/Filament/Resources/UserResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('User Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\Select::make('role')
                        ->options([
                            'admin' => 'Admin',
                            'vendor' => 'Vendor',
                        ])
                        ->required()
                        ->live()
                        ->native(false),
                    
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->required(fn ($record) => $record === null)
                        ->maxLength(255)
                        ->dehydrateStateUsing(fn ($state) => !empty($state) ? bcrypt($state) : null)
                        ->visibleOn('create'),
                    
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->required(),
                ])->columns(2),
            
            // 🔥 Vendor Information
            Forms\Components\Section::make('Vendor Information')
                ->schema([
                    Forms\Components\FileUpload::make('vendor.vendor_logo')
                        ->label('Vendor Logo')
                        ->image()
                        ->imageEditor()
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('200')
                        ->imageResizeTargetHeight('200')
                        ->directory('vendors/logos')
                        ->visibility('public')
                        ->helperText('Upload square logo (Recommended: 200x200)')
                        ->columnSpanFull(),
                    
                    Forms\Components\TextInput::make('vendor.vendor_name')
                        ->label('Vendor/Business Name')
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->maxLength(255)
                        ->placeholder('Enter business/vendor name'),
                    
                    Forms\Components\TextInput::make('vendor.isbn_number')
                        ->label('ISBN Number')
                        ->helperText('International Standard Book Number - 10 or 13 digit')
                        ->placeholder('e.g., 978-3-16-148410-0')
                        ->maxLength(20)
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor'),
                    
                    Forms\Components\TextInput::make('vendor.vendor_phone')
                        ->label('Phone Number')
                        ->tel()
                        ->maxLength(20)
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->placeholder('Enter contact number'),
                    
                    Forms\Components\Textarea::make('vendor.vendor_address')
                        ->label('Address')
                        ->rows(3)
                        ->maxLength(500)
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->placeholder('Enter complete address')
                        ->columnSpanFull(),
                    
                    Forms\Components\TextInput::make('vendor.vendor_website')
                        ->label('Website (Optional)')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://example.com'),
                ])
                ->columns(2)
                ->visible(fn (Forms\Get $get) => $get('role') === 'vendor')
                ->collapsible(),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 🔥 Vendor Logo in Table
                Tables\Columns\ImageColumn::make('vendor.vendor_logo')
                    ->label('Logo')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(function ($record) {
                        if ($record->vendor && $record->vendor->vendor_name) {
                            return 'https://ui-avatars.com/api/?name=' . urlencode($record->vendor->vendor_name) . '&color=7F9CF5&background=EBF4FF';
                        }
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF';
                    }),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'vendor' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'vendor' => 'Vendor',
                        default => $state,
                    })
                    ->sortable(),
                
                // 🔥 Vendor Name
                Tables\Columns\TextColumn::make('vendor.vendor_name')
                    ->label('Vendor Name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                
                // 🔥 ISBN Number
                Tables\Columns\TextColumn::make('vendor.isbn_number')
                    ->label('ISBN')
                    ->placeholder('N/A')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                
                // 🔥 Vendor Phone
                Tables\Columns\TextColumn::make('vendor.vendor_phone')
                    ->label('Phone')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d-M-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'vendor' => 'Vendor',
                    ])
                    ->placeholder('All Roles'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active users')
                    ->trueLabel('Only active users')
                    ->falseLabel('Only inactive users')
                    ->placeholder('All users'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}