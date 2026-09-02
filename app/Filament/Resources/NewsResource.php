<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Filament\Resources\NewsResource\RelationManagers;
use App\Models\News;
use App\Models\NewsCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Forms\Set;




class NewsResource extends Resource
{
    protected static ?string $model = News::class;

   protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'News Management';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
    public static function form(Form $form): Form
    {
       return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
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
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(80)
                    ->unique(ignoreRecord: true)
                    ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                    $slug = str_replace('/', '-', $state); // Remove "/"
                                    $slug = Str::slug($slug);             // Convert to slug

                                    $set('slug', $slug);
                            }),
                Forms\Components\Select::make('news_category_id')
                    ->label('Category')
                    ->options(NewsCategory::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('feature_image')
                    ->image()
                    ->directory('news-images')
                    ,
                Forms\Components\RichEditor::make('content')
                    ,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('feature_image')
                    ->square(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
               Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
