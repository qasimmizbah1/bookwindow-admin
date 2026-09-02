<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;
use App\Imports\ProductsImport;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Category;
use Filament\Forms\Set;
use App\Models\Vendor;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationGroup = "Shop";
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $recordTitleAttribute = "name";
    protected static ?string $navigationLabel = 'Products';

    public static function getNavigationBadge(): ?string
    {
        //return static::getModel()::count();
        if (auth()->user()->isAdmin()) {
             return static::getModel()::count();
        }
        if (auth()->user()->isVendor()) {
            $vendor = auth()->user()->vendor;
            if ($vendor) {
                return static::getModel()::where('vendor_id', $vendor->id)->count();
            }
            return 0;
        }
    
        return null;
    }

    // Vendor Scoping - Vendor ko sirf apne products dikhe
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->isVendor()) {
            $vendor = auth()->user()->vendor;
            if ($vendor) {
                $query->where('vendor_id', $vendor->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Update Information Section
                Forms\Components\Section::make('Update Information')
                    ->schema([
                        Forms\Components\Placeholder::make('last_updated_at')
                            ->label('Last Updated')
                            ->content(function (?Product $record) {
                                return $record?->updated_at
                                    ? $record->updated_at->format('d M Y, h:i A')
                                    : 'Not updated yet';
                            }),
                        Forms\Components\Placeholder::make('updated_by')
                            ->label('Updated By')
                            ->content(function (?Product $record) {
                                return $record?->updatedBy?->name ?? 'Not available';
                            }),
                    ])
                    ->columns(2)
                    ->visible(fn (string $operation) => $operation === 'edit'),

                // Main Group
                Forms\Components\Group::make()
                    ->schema([
                        // Vendor Field - Sirf Admin ko dikhe, aur conditional visibility
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->options(
                                auth()->user()->isAdmin() 
                                    ? Vendor::pluck('vendor_name', 'id')
                                    : Vendor::where('user_id', auth()->id())->pluck('vendor_name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->visible(function ($record) {
                                if (!auth()->user()->isAdmin()) {
                                    return false;
                                }
                                if ($record && is_null($record->vendor_id)) {
                                    return false;
                                }
                                return true;
                            })
                            ->helperText('Select vendor or leave empty for admin product'),

                        // Product Type Badge
                        Forms\Components\Placeholder::make('product_type')
                            ->label('Product Type')
                            ->content(function ($record) {
                                if ($record && is_null($record->vendor_id)) {
                                    return 'Admin Product (No Vendor)';
                                }
                                if ($record && $record->vendor_id) {
                                    return 'Vendor Product';
                                }
                                return 'New Product';
                            })
                            ->visible(fn ($record) => $record && auth()->user()->isAdmin())
                            ->columnSpanFull(),

                        // Basic Info Section
                        Forms\Components\Section::make('Basic Info')
                            ->schema([
                                // Category
                                Select::make('category_id')
                                    ->label('Categories')
                                    ->multiple()
                                    ->options(Category::pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                // Title
                                Forms\Components\TextInput::make('name')
                                    ->label('Title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }
                                        $slug = Str::slug($state);
                                        if (strlen($slug) > 55) {
                                            $slug = substr($slug, 0, 55);
                                            $slug = preg_replace('/-[^-]*$/', '', $slug);
                                        }
                                        $set('slug', $slug);
                                    }),

                                // Slug
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->live()
                                    ->maxLength(55)
                                    ->unique(ignoreRecord: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $slug = str_replace('/', '-', $state);
                                        $slug = Str::slug($slug);
                                        $set('slug', $slug);
                                    }),

                                Forms\Components\TextInput::make('sub_title')
                                    ->label('Sub Title'),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->required(),

                                Forms\Components\MarkdownEditor::make('description')
                                    ->label('Product Description')
                                    ->required(),

                                Forms\Components\TextInput::make('meta_tag_title'),
                                Forms\Components\MarkdownEditor::make('meta_tag_description'),
                                Forms\Components\MarkdownEditor::make('meta_tag_keywords'),
                            ]),

                        // Image Section
                        Forms\Components\Section::make('Product Image')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Product Main Image')
                                    ->required()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products'),
                                Forms\Components\FileUpload::make('gallery')
                                    ->label('Product Gallery')
                                    ->multiple()
                                    ->image()
                                    ->reorderable()
                                    ->appendFiles()
                                    ->imageEditor()
                                    ->directory('products/gallery'),
                            ])->collapsible(),
                    ]),

                // Right Column Group
                Forms\Components\Group::make()
                    ->schema([
                        //  Status Section
                        Forms\Components\Section::make('Status')
                            ->schema([
                                //  Status Toggle - Sirf Admin ko dikhe
                                Forms\Components\Toggle::make('is_visible')
                                    ->label('Status')
                                    ->default(false)
                                    ->visible(auth()->user()->isAdmin())
                                    ->helperText(function ($record) {
                                        if ($record && is_null($record->vendor_id)) {
                                            return 'Admin product - Toggle to enable/disable';
                                        }
                                        if ($record && $record->vendor_id) {
                                            return 'Vendor product - Enable to make it live on website';
                                        }
                                        return 'Product will be saved as disabled by default';
                                    })
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->onIcon('heroicon-o-check-circle')
                                    ->offIcon('heroicon-o-x-circle'),

                                //  Vendor Status Info
                                Forms\Components\Placeholder::make('vendor_status_info')
                                    ->label('Product Status')
                                    ->content(function ($record) {
                                        if (auth()->user()->isVendor()) {
                                            if ($record && $record->is_visible == 1) {
                                                return 'Active (Enabled by Admin)';
                                            }
                                            return ' Pending - Waiting for Admin approval';
                                        }
                                        return '';
                                    })
                                    ->visible(auth()->user()->isVendor())
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('model')
                                    ->required(),
                                Forms\Components\TextInput::make('author')
                                    ->required(),
                                Forms\Components\TextInput::make('year')
                                    ->required(),
                                Forms\Components\Select::make('production_id')
                                    ->label('Publication')
                                    ->relationship('production', 'name')
                                    ->required(),
                            ]),

                        //  Price Section
                        Forms\Components\Section::make('Price & Details')
                            ->schema([
                                Forms\Components\TextInput::make('mrp')
                                    ->label('MRP')
                                    ->required()
                                    ->numeric()
                                    ->prefix(currency_symbol()),
                                Forms\Components\TextInput::make('price')
                                    ->label('Sales Price')
                                    ->required()
                                    ->numeric()
                                    ->prefix(currency_symbol()),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Stock Quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('number_of_pages')
                                    ->label('Number of Pages')
                                    ->numeric(),
                                Forms\Components\TextInput::make('book_language')
                                    ->required(),
                                Forms\Components\TextInput::make('weight')
                                    ->label('Weight (in KG)')
                                    ->required()
                                    ->numeric(),
                                Forms\Components\TextInput::make('isbn'),
                                Forms\Components\TextInput::make('isbn10'),
                                Forms\Components\TextInput::make('isbn13'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'Hindi' => 'Hindi',
                                        'English' => 'English',
                                        'Other' => 'Other',
                                    ])
                                    ->label('Medium')
                                    ->required(),
                                Forms\Components\DatePicker::make('published_at')
                                    ->label('Publication Date')
                                    ->required(),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ID
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                
                
                // Image
                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('vendor_display')
                    ->label('Vendor')
                    ->state(function ($record) {
                        return $record->vendor_id
                            ? ($record->vendor?->vendor_name ?? 'N/A')
                            : 'Admin';
                    })
                    ->badge()
                    ->color(fn ($record) => is_null($record->vendor_id) ? 'success' : 'gray')
                    ->visible(fn () => auth()->user()->isAdmin()),
                    
                
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->limit(30),

                
                // Publication
                Tables\Columns\TextColumn::make('production.name')
                    ->label("Publication")
                    ->sortable()
                    ->searchable(),
                
                //  Status
                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->label('Status'),
                
                // Price
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                
                // Quantity
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : 'success'),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make(),
                Action::make('import')
                    ->label('Import Products')
                    ->action(function (array $data) {
                        $file = storage_path('app/public/' . $data['file']);
                        if (!file_exists($file)) {
                            throw new \Exception("File not found: {$file}");
                        }
                        Excel::import(new ProductsImport(), $file);
                    })
                    ->form([
                        FileUpload::make('file')
                            ->label('CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'text/plain', '.csv']),
                    ]),
            ])
            ->filters([
                // Filter: Admin Products
                Tables\Filters\Filter::make('admin_products')
                    ->label('Admin Products')
                    ->query(fn ($query) => $query->whereNull('vendor_id'))
                    ->visible(auth()->user()->isAdmin()),
                
                // Filter: Vendor Products
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Vendor Products')
                    ->options(Vendor::pluck('vendor_name', 'id'))
                    ->visible(auth()->user()->isAdmin()),
                
                // Filter: Active Products
                Tables\Filters\Filter::make('active')
                    ->label('Active Products')
                    ->query(fn ($query) => $query->where('is_visible', 1)),
                
                // Filter: Inactive Products
                Tables\Filters\Filter::make('inactive')
                    ->label('Inactive Products')
                    ->query(fn ($query) => $query->where('is_visible', 0)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Product')
                        ->modalDescription('Are you sure you want to delete this product? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'import' => Pages\ImportProducts::route('/import'),
        ];
    }
}