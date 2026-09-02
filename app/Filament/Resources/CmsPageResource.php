<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CmsPageResource\Pages;
use App\Filament\Resources\CmsPageResource\RelationManagers;
use App\Models\CmsPage;
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

class CmsPageResource extends Resource
{
    protected static ?string $model = CmsPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Pages';
    protected static ?string $modelLabel = 'Page';
    protected static ?string $pluralModelLabel = 'Pages';
    
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
                        ->content(function (?CmsPage $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?CmsPage $record) {
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
            
            Forms\Components\FileUpload::make('banner_images')
                ->label('Banner Image')
                ->image()
                ->directory('cms-pages/banner')
                ->reorderable()
                ->columnSpan(2),    
            
            
            Textarea::make('short_description')
                ->columnSpan(2),

            RichEditor::make('content')
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
            Tables\Columns\TextColumn::make('title'),
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
            'index' => Pages\ListCmsPages::route('/'),
            'create' => Pages\CreateCmsPage::route('/create'),
            'edit' => Pages\EditCmsPage::route('/{record}/edit'),
        ];
    }
}
