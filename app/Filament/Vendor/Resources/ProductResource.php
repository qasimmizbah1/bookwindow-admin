<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\ProductResource\Pages;
use App\Filament\Vendor\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;
use App\Imports\ProductsImport;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Vendor;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $vendor = Vendor::where('user_id', auth()->id())->first();
        
        return $form
            ->schema([
                
                //Start First Section
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make(heading:'Basic Info')
                        ->schema([

                        //Category
                        Forms\Components\Select::make(name: 'category_id')
                        ->relationship(
                        name:'category', 
                        titleAttribute:'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereNull('parent_id')
                        )
                        ->required(),

                             
                        
                        //Sub Category
                             Forms\Components\Select::make('sub_category_id')
                             ->label('Sub Category')
                             ->options(function (callable $get) {
                                 $categoryId = $get('category_id');
                                 
                                 if (!$categoryId) {
                                     return [];
                                 }
                                 
                                 
                                 return \App\Models\Category::where('parent_id', $categoryId)
                                 ->pluck('name', 'id');
                             })
                             
                             ->live() // Add this to make it update on change
                             ->afterStateUpdated(fn (callable $set) => $set('child_category_id', null)),
 
                        // Child Category
                            Forms\Components\Select::make('child_category_id')
                            ->label('Child Category')
                            ->options(function (callable $get) {
                            $subCategoryId = $get('sub_category_id');

                            if (!$subCategoryId) {
                            return [];
                            }

                            return \App\Models\Category::where('parent_id', $subCategoryId)
                            ->pluck('name', 'id');
                            })
                            ->required(function (callable $get) {
                            $subCategoryId = $get('sub_category_id');
                            if (!$subCategoryId) return false;

                        // Only required if the sub-category has children
                            return \App\Models\Category::where('parent_id', $subCategoryId)->exists();
                            })
                            ->hidden(fn (callable $get): bool => !\App\Models\Category::where('parent_id', $get('sub_category_id'))->exists()),

                        //End of Category

                            Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation !== 'create') {
                            return;
                            }
                            $set('slug', Str::slug($state));
                            }),

                            Forms\Components\TextInput::make('slug')
                            ->required()
                            ->live(),


                            Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required(),
                                                    
                            Forms\Components\MarkdownEditor::make('description')
                            ->label('Product Description'),

                            Forms\Components\TextInput::make('meta_tag_title'),
                            Forms\Components\MarkdownEditor::make('meta_tag_description'),
                            Forms\Components\MarkdownEditor::make('meta_tag_keywords'),
                            
                            
                        ]),

                        //End First Section

                        //Start Image Section
                        Forms\Components\Section::make(heading:'Product Image')
                        ->schema([
                            Forms\Components\FileUpload::make(name:'image')
                            ->label('Product Main Image')
                            ->required(),

                            Forms\Components\FileUpload::make(name:'gallery')
                            ->label('Product Gallery')
                            ->multiple()
                            ->image()
                            ->reorderable()
                            ->appendFiles()
                            ->imageEditor()
                        
                        ])->collapsible(),
                        
                        //End image Section

                       

                        


                ]),

                 
                Forms\Components\Group::make()
                ->schema([

                     //Start Second Section

                    Forms\Components\Section::make(heading:'Status')
                        ->schema([
                            
                            Forms\Components\Toggle::make('is_visible')
                            ->label('Status'),
                            Forms\Components\TextInput::make('model'),
                            Forms\Components\TextInput::make('author'),
                            Forms\Components\TextInput::make('year'),
                            Forms\Components\Select::make(name: 'production_id')
                            ->relationship(name:'production', titleAttribute:'name')
                            ->required(),
                          
                            
                        ]),

                    //End Second Section
                    
                    //Start Third Section Price
                        Forms\Components\Section::make(heading:'Price')
                        ->schema([
                            Forms\Components\TextInput::make('mrp')
                            ->label('MRP'),
                            Forms\Components\TextInput::make('price')
                            ->label('Sales Price'),
                            Forms\Components\TextInput::make('quantity')
                            ->label('Stock Quantity'),
                            Forms\Components\TextInput::make('number_of_pages')
                            ->label('Number of Pages'),
                            Forms\Components\TextInput::make('book_language'),
                            Forms\Components\TextInput::make('weight')
                            ->label('Weight (in KG)'),
                            Forms\Components\TextInput::make('isbn'),
                            Forms\Components\TextInput::make('isbn10'),
                            Forms\Components\TextInput::make('isbn13'),
                            Forms\Components\Select::make('type')->options([

                                'Hindi'=>'Hindi',
                                'English'=>'English',
                                'Other'=>'Other',


                            ])
                            ->label('Medium'),
                            Forms\Components\DatePicker::make('published_at')
                            ->label('Publication Date')
                            ->required(),
                            Forms\Components\Hidden::make('vendor_id')
                                ->default(fn () => $vendor->id),
                            

                            ])->columns(2),
                ])
                            
                            

            ]);
                        
            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 Tables\Columns\TextColumn::make(name: 'id')
                 ->sortable()
                 ->searchable(),
                Tables\Columns\ImageColumn::make(name: 'image'),
                Tables\Columns\TextColumn::make(name: 'name')
                ->sortable()
                 ->searchable()
                 ->limit('20'),
                Tables\Columns\TextColumn::make(name: 'production.name')
                ->label("Publication")
                ->sortable()
                 ->searchable(),
                Tables\Columns\IconColumn::make(name: 'is_visible')
                    ->boolean(),
                Tables\Columns\TextColumn::make(name: 'price'),
                Tables\Columns\TextColumn::make(name: 'quantity'),

            ])
            ->defaultSort('id', 'desc')
            // ->headerActions([
            //     CreateAction::make(),
            //     Action::make('import')
            //         ->label('Import Products')
            //         ->action(function (array $data) {
            //             $file = storage_path('app/public/' . $data['file']);
            //             if (!file_exists($file)) {
            //                 throw new \Exception("File not found: " . $file);
            //             }

            //             Excel::import(new ProductsImport(), $file);
            //         })
            //         ->form([
            //             FileUpload::make('file')
            //                 ->label('Excel File')
            //                 ->required()
            //                 ->acceptedFileTypes([
            //                     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            //                     'application/vnd.ms-excel',
            //                 ]),
            //         ]),
            // ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

        // public static function getEloquentQuery(): Builder
        // {
        // $query = parent::getEloquentQuery();

        // if (auth()->check() && auth()->user()->vendor) {
        // $query->where('vendor_id', auth()->user()->vendor->id);
        // }

        // return $query;
        // }

            public static function getEloquentQuery(): Builder
            {
            $query = parent::getEloquentQuery();

            // Apply vendor scope only if the user is a vendor
            if (auth()->check()) {
            $vendor = \App\Models\Vendor::where('user_id', auth()->id())->first();

            if ($vendor) {
            // Always enforce vendor_id filter (even for edits)
            $query->where('vendor_id', $vendor->id);
            } else {
            // If not a vendor, show nothing (or adjust as needed)
            $query->where('vendor_id', -1); // Force empty result
            }
            }

            return $query;
            }





}
