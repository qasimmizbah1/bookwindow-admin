<?php
// app/Filament/Resources/HomePageResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\HomePageResource\Pages;
use App\Filament\Resources\HomePageResource\RelationManagers;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\Category;
use App\Models\Production;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;


class HomePageResource extends Resource
{
    protected static ?string $model = HomePage::class;
    protected static ?string $navigationGroup = 'Pages';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $modelLabel = 'Home Page';
    protected static ?string $navigationLabel = 'Home Page';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('home_page') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Update Information')
                ->schema([
                    Forms\Components\Placeholder::make('last_updated_at')
                        ->label('Last Updated')
                        ->content(function (?HomePage $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?HomePage $record) {
                            return $record?->updatedBy?->name ?? 'Not available';
                        }),
                ])
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'edit'),      

                // Slider Section
                    Forms\Components\Repeater::make('slider_section')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\FileUpload::make('slider_image')
                            ->image()
                            ->disk('public')
                            ->directory('home-page/banner'),

                        Forms\Components\TextInput::make('slider_url'),
                    ]),

                //Mobile Slider

                Forms\Components\Repeater::make('mslider_section')
                    ->label("Mobile Slider Section  ")
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\FileUpload::make('mslider_image')
                            ->label("Image")
                            ->image()
                            ->disk('public')
                            ->directory('home-page/banner'),

                        Forms\Components\TextInput::make('mslider_url')
                            ->label("URL"),
                    ]),




                    Forms\Components\Section::make('Popular Categories')
                    ->schema([
                        Forms\Components\TextInput::make('popular_title')
                                    ->label('Title')
                                    ->required(),

                        Forms\Components\TextInput::make('popular_subtitle')
                                    ->label('Sub Title')
                                    ->required(),

                        Forms\Components\Repeater::make('popular_category')
                            ->label('Popular Categories (Drag & Drop to set order)')
                            ->simple(
                                Forms\Components\Select::make('category_id')
                                    ->options(Category::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                            )
                            ->addActionLabel('Add Category')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])->columns(2),

                    Forms\Components\Section::make('Best Sellers')
                    ->schema([
                        Forms\Components\TextInput::make('best_sellers_title')
                                    ->label('Title')
                                    ->default('Best Sellers')
                                    ->required(),

                        Forms\Components\TextInput::make('best_sellers_subtitle')
                                    ->label('Sub Title')
                                    ->default('Explore our top bestselling books'),

                        Forms\Components\Repeater::make('best_sellers')
                            ->label('Best Seller Products (Drag & Drop to set order)')
                            ->simple(
                                Forms\Components\Select::make('product_id')
                                    ->options(Product::visibleToCustomers()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                            )
                            ->addActionLabel('Add Best Seller Product')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])->columns(2),

                    Forms\Components\Section::make('Mock Tests')
                    ->schema([
                        Forms\Components\TextInput::make('mock_subtitle')
                                    ->label('Sub Title')
                                    ->required(),
                        Forms\Components\Repeater::make('mock_test_category')
                            ->label('Mock Test Categories (Drag & Drop to set order)')
                            ->simple(
                                Forms\Components\Select::make('category_id')
                                    ->options(Category::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                            )
                            ->addActionLabel('Add Category')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])->columns(1),

                    Forms\Components\Section::make('Hobby')
                    ->schema([
                        Forms\Components\TextInput::make('hobby_subtitle')
                                    ->label('Sub Title')
                                    ->required(),
                        Forms\Components\Repeater::make('hobby_category')
                            ->label('Hobby Categories (Drag & Drop to set order)')
                            ->simple(
                                Forms\Components\Select::make('category_id')
                                    ->options(Category::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                            )
                            ->addActionLabel('Add Category')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])->columns(1),

                    
                Forms\Components\Section::make('Publications')
                ->schema([
                    Forms\Components\TextInput::make('publications_subtitle')
                                ->label('Sub Title')
                                ->required(),
                    Forms\Components\Repeater::make('publication')
                        ->label('Publications (Drag & Drop to set order)')
                        ->simple(
                            Forms\Components\Select::make('publication_id')
                                ->options(Production::pluck('name', 'id'))
                                ->searchable()
                                ->required()
                        )
                        ->addActionLabel('Add Publication')
                        ->reorderable()
                        ->columnSpanFull(),
                ])->columns(1),

                Forms\Components\Section::make('Banner Section')
                    ->schema([

                        
                        
                        Forms\Components\FileUpload::make('banner_images')
                            ->label('Banner Image')
                            ->image()
                            ->disk('public')
                            ->directory('home-page/banner')
                            ->reorderable(),
                         
                        
                        
                        
                        Forms\Components\TextInput::make('banner_button_url')
                            ->label('Button URL'),

                    ]) ->columns(2),
                
                    //SEO
                    Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_tag_title'),
                        Forms\Components\MarkdownEditor::make('meta_tag_description'),
                        Forms\Components\MarkdownEditor::make('meta_tag_keywords')
                        ])
                        ]);

                        
                        
                            


                     // // Featured Products Section
                     //        Forms\Components\Section::make('Featured Products')
                     //        ->schema([
                     //        Forms\Components\TextInput::make('featured_products_title')
                     //        ->label('Section Title'),

                     //        Select::make('featured_products')
                     //        ->label('Select Products')
                     //        ->options(Product::pluck('name', 'id'))
                     //        ->multiple()
                     //        ->searchable(['name', 'sku'])
                     //        ->afterStateUpdated(fn ($state) => is_array($state) ? implode(',', $state) : $state)
                     //        ->columnSpanFull(),
                     //        ]),



                // Best Sellers Section
                // Forms\Components\Section::make('Best Sellers')
                //     ->schema([
                //         Forms\Components\TextInput::make('best_sellers_title')
                //             ->label('Section Title'),
                //         Forms\Components\Select::make('best_sellers')
                //             ->label('Select Products')
                //             ->multiple()
                //             ->relationship('bestSellers', 'name')
                //             ->preload()
                //             ->columnSpanFull(),
                //     ]),
                
                // Latest Products Section
                // Forms\Components\Section::make('Latest Products')
                //     ->schema([
                //         Forms\Components\TextInput::make('latest_products_title')
                //             ->label('Section Title'),
                //         // Latest products will be fetched dynamically in the frontend
                //     ]),
                
                // Categories Section
                // Forms\Components\Section::make('Categories')
                //     ->schema([
                //         Forms\Components\Repeater::make('categories')
                //             ->label('Categories')
                //             ->schema([
                //                 Forms\Components\Select::make('category_id')
                //                     ->label('Category')
                //                     ->relationship('categories', 'name') // Assuming you have a Category model
                //                     ->required(),
                //                 Forms\Components\TextInput::make('button_text')
                //                     ->label('Button Text')
                //                     ->default('View More'),
                //             ])
                //             ->columns(2)
                //             ->columnSpanFull(),
                //     ]),
                
                
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 Tables\Columns\TextColumn::make('page_title')
                    ->label('Page')
                    ->weight('bold')
                    ,
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListHomePages::route('/'),
            'create' => Pages\CreateHomePage::route('/create'),
            'edit' => Pages\EditHomePage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationUrl(): string
{
    $recordId = \App\Models\HomePage::query()->first()?->id;

    return $recordId
        ? static::getUrl('edit', ['record' => $recordId])
        : static::getUrl('index'); // fallback
}

}