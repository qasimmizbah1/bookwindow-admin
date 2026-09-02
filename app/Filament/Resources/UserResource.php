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
            
            // 🔥 Commission & Account Status
            Forms\Components\Section::make('Commission & Platform Settings')
                ->schema([
                    Forms\Components\TextInput::make('vendor.commission_percentage')
                        ->label('Platform Commission (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(7.00)
                        ->suffix('%')
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->helperText('Platform fee % deducted from each sale. Default is 7.00%. Admin can customize per vendor.'),
                    
                    Forms\Components\Select::make('vendor.approval_status')
                        ->label('Vendor Approval Status')
                        ->options([
                            'approved' => 'Approved (Active Seller)',
                            'pending' => 'Pending Verification',
                            'suspended' => 'Suspended (Temporary Block)',
                        ])
                        ->default('approved')
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->native(false),
                ])
                ->columns(2)
                ->visible(fn (Forms\Get $get) => $get('role') === 'vendor'),

            // 🔥 Store & Contact Information
            Forms\Components\Section::make('Store & Warehouse Information')
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
                        ->label('Store / Business Name')
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->maxLength(255)
                        ->placeholder('e.g. Royal Book Store'),

                    Forms\Components\TextInput::make('vendor.contact_person')
                        ->label('Contact Person Name')
                        ->maxLength(255)
                        ->placeholder('e.g. Ramesh Sharma'),
                    
                    Forms\Components\TextInput::make('vendor.vendor_phone')
                        ->label('Support Phone Number')
                        ->tel()
                        ->maxLength(20)
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->placeholder('e.g. +91 9876543210'),

                    Forms\Components\TextInput::make('vendor.vendor_website')
                        ->label('Website (Optional)')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://example.com'),
                    
                    Forms\Components\Textarea::make('vendor.vendor_address')
                        ->label('Pickup / Warehouse Address')
                        ->rows(2)
                        ->maxLength(500)
                        ->required(fn (Forms\Get $get) => $get('role') === 'vendor')
                        ->placeholder('Shop no, Street, Landmark')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('vendor.city')
                        ->label('City')
                        ->maxLength(100)
                        ->placeholder('e.g. Jaipur'),

                    Forms\Components\TextInput::make('vendor.state')
                        ->label('State')
                        ->maxLength(100)
                        ->placeholder('e.g. Rajasthan'),

                    Forms\Components\TextInput::make('vendor.pincode')
                        ->label('Pincode (Pickup)')
                        ->maxLength(10)
                        ->placeholder('e.g. 302020'),
                ])
                ->columns(3)
                ->visible(fn (Forms\Get $get) => $get('role') === 'vendor')
                ->collapsible(),

            // 🔥 Tax & Legal Verification
            Forms\Components\Section::make('Tax & Legal Verification')
                ->schema([
                    Forms\Components\TextInput::make('vendor.pan_number')
                        ->label('PAN Number')
                        ->maxLength(20)
                        ->placeholder('e.g. ABCDE1234F')
                        ->helperText('Business or Personal PAN for tax verification'),

                    Forms\Components\TextInput::make('vendor.gst_number')
                        ->label('GSTIN / GST Number')
                        ->maxLength(20)
                        ->placeholder('e.g. 08AAAAA0000A1Z5')
                        ->helperText('Optional if turnover under threshold'),

                    Forms\Components\TextInput::make('vendor.isbn_number')
                        ->label('ISBN / Publisher License')
                        ->placeholder('e.g. 978-3-16-148410-0')
                        ->maxLength(50)
                        ->helperText('Publisher registration / ISBN identification'),
                ])
                ->columns(3)
                ->visible(fn (Forms\Get $get) => $get('role') === 'vendor')
                ->collapsible(),

            // 🔥 Bank Account & Payout Details
            Forms\Components\Section::make('Bank Account & Payout Details')
                ->schema([
                    Forms\Components\TextInput::make('vendor.bank_name')
                        ->label('Bank Name')
                        ->placeholder('e.g. State Bank of India')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('vendor.account_holder_name')
                        ->label('Account Holder Name')
                        ->placeholder('Name as per Bank Passbook')
                        ->maxLength(150),

                    Forms\Components\TextInput::make('vendor.account_number')
                        ->label('Bank Account Number')
                        ->placeholder('e.g. 123456789012')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('vendor.ifsc_code')
                        ->label('IFSC Code')
                        ->placeholder('e.g. SBIN0001234')
                        ->maxLength(20),

                    Forms\Components\TextInput::make('vendor.upi_id')
                        ->label('UPI ID (Optional)')
                        ->placeholder('e.g. store@upi')
                        ->maxLength(100),
                ])
                ->columns(3)
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
                    ->label('Store Name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),

                // 🔥 Commission Rate
                Tables\Columns\TextColumn::make('vendor.commission_percentage')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state, $record) => $record->isVendor() ? ($state ?? '7.00') . '%' : '-')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                
                // 🔥 Approval Status
                Tables\Columns\TextColumn::make('vendor.approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->visible(fn () => true)
                    ->toggleable(isToggledHiddenByDefault: true),
                
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