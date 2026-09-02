<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use App\Imports\CategoriesImport;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Set;



class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationGroup = "Shop";

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

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

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }
    
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
                Forms\Components\Section::make('Update Information')
                ->schema([
                    Forms\Components\Placeholder::make('last_updated_at')
                        ->label('Last Updated')
                        ->content(function (?Category $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?Category $record) {
                            return $record?->updatedBy?->name ?? 'Not available';
                        }),
                ])
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'edit'),
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make(heading:'Basic Info')
                        ->schema([
                            Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                            Forms\Components\TextInput::make('name')
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

                            // Forms\Components\TextInput::make('slug')
                            // ->required()
                            // ->live()
                            // ->suffix(function (callable $get) {
                            // return $get('parent_id') 
                            // ? '-' . \App\Models\Category::find($get('parent_id'))?->slug 
                            // : '-books';
                            // }),

                            
                            Forms\Components\TextInput::make('slug')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                    $slug = str_replace('/', '-', $state); // Remove "/"
                                    $slug = Str::slug($slug);             // Convert to slug

                                    $set('slug', $slug);
                            }),


                            Forms\Components\MarkdownEditor::make('description'),
                            Forms\Components\FileUpload::make('cat_image')
                            ->label('Category Image'),
                            
                            
                        ]),
                    ]),
                    Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make(heading:'SEO')
                        ->schema([
                            Forms\Components\TextInput::make('meta_tag_title'),
                            Forms\Components\MarkdownEditor::make('meta_tag_description'),
                            Forms\Components\MarkdownEditor::make('meta_tag_keywords'),
                            
                            
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make(name: 'cat_image')->searchable(),
                Tables\Columns\TextColumn::make(name: 'id')->searchable(),
                Tables\Columns\TextColumn::make(name: 'name')->searchable(),
                Tables\Columns\TextColumn::make(name: 'parent.name'),
                Tables\Columns\TextColumn::make(name: 'slug'),
                
                
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make(),
                Action::make('import')
                    ->label('Import Categories')
                    ->action(function (array $data) {
                        $file = storage_path('app/public/' . $data['file']);
                        if (!file_exists($file)) {
                            throw new \Exception("File not found: " . $file);
                        }

                        Excel::import(new CategoriesImport(), $file);
                    })
                    ->form([
                        FileUpload::make('file')
                            ->label('Excel File')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ]),
                    ]),
            ])
            
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
            'import' => Pages\ImportCategories::route('/import'),
        ];
    }
}
