<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\CategoryResource\Pages;
use App\Filament\Vendor\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
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
use Filament\Forms\Components\Select;
use App\Models\Vendor;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {

          $vendor = Vendor::where('user_id', auth()->id())->first();
          

        return $form
            ->schema([
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make(heading:'Basic Info')
                        ->schema([
                            Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship(
                            name: 'parent',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query, $record) => $query->when(
                            $vendor->id,
                            fn ($q) => $q->where('vendor_id', $vendor->id)
                            )
                            )
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
                            $set('slug', Str::slug($state));
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
                            ->required(),


                            Forms\Components\MarkdownEditor::make('description'),
                                Forms\Components\Hidden::make('vendor_id')
                                ->default(fn () => $vendor->id),
                            
                            
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
                Tables\Columns\TextColumn::make(name: 'id')->searchable(),
                Tables\Columns\TextColumn::make(name: 'name')->searchable(),
                Tables\Columns\TextColumn::make(name: 'parent.name'),
                Tables\Columns\TextColumn::make(name: 'slug'),
                
                
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
        ];
    }

        // public static function getEloquentQuery(): Builder
        // {
        // $query = parent::getEloquentQuery();

        // if (auth()->check()) {

        // $vendor = \App\Models\Vendor::where('user_id', auth()->id())->first();
        // if ($vendor) {
        // $query->where('vendor_id', $vendor->id);
        // }


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
