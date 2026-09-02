<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CmsPostResource\Pages;
use App\Filament\Resources\CmsPostResource\RelationManagers;
use App\Models\CmsPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Str;
use Filament\Forms\Set;


class CmsPostResource extends Resource
{
    protected static ?string $model = CmsPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil';
    protected static ?string $navigationGroup = 'Blog';
    protected static ?int $navigationSort = 2;
     protected static ?string $modelLabel = 'Blog Post';
    protected static ?string $pluralModelLabel = 'Blog Posts';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            Forms\Components\Section::make('Update Information')
                ->schema([
                    Forms\Components\Placeholder::make('last_updated_at')
                        ->label('Last Updated')
                        ->content(function (?CmsPost $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?CmsPost $record) {
                            return $record?->updatedBy?->name ?? 'Not available';
                        }),
                ])
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'edit'),
           
            TextInput::make('title')
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
                TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                    $slug = str_replace('/', '-', $state); // Remove "/"
                                    $slug = Str::slug($slug);             // Convert to slug

                                    $set('slug', $slug);
                            }),
                Select::make('cms_category_id')
                ->relationship('category', 'name')
                ->required()
                ->columnSpan(2),
            RichEditor::make('content')
            ->columnSpan(2),
            Forms\Components\FileUpload::make(name:'image')
            ->label('Feature Image')
            ->required()
            ->columnSpan(2),
            Forms\Components\TextInput::make('meta_title')
                    ->maxLength(255),
                 Forms\Components\TextInput::make('meta_keywords')
                    ->maxLength(255),

                Forms\Components\Textarea::make('meta_description')
                    ->maxLength(255)
                    ->columnSpanfull(),

            Toggle::make('is_active')->label('Active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
             Tables\Columns\ImageColumn::make(name: 'image'),
            Tables\Columns\TextColumn::make('title'),
            Tables\Columns\TextColumn::make('category.name')->label('Category'),
            Tables\Columns\TextColumn::make('slug'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
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
            'index' => Pages\ListCmsPosts::route('/'),
            'create' => Pages\CreateCmsPost::route('/create'),
            'edit' => Pages\EditCmsPost::route('/{record}/edit'),
        ];
    }
}
