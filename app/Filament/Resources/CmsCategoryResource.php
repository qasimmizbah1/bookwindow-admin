<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CmsCategoryResource\Pages;
use App\Filament\Resources\CmsCategoryResource\RelationManagers;
use App\Models\CmsCategory;
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
use Illuminate\Support\Str;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Set;

class CmsCategoryResource extends Resource
{
    protected static ?string $model = CmsCategory::class;

    protected static ?string $navigationIcon = 'heroicon-c-at-symbol';
    protected static ?string $navigationGroup = 'Blog';
    protected static ?int $navigationSort = 1;
    protected static ?string $pluralModelLabel = 'Blog Categories';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }


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
                        ->content(function (?CmsCategory $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?CmsCategory $record) {
                            return $record?->updatedBy?->name ?? 'Not available';
                        }),
                ])
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'edit'),


                TextInput::make('name')
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
             RichEditor::make('content')
            ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('slug'),
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
            'index' => Pages\ListCmsCategories::route('/'),
            'create' => Pages\CreateCmsCategory::route('/create'),
            'edit' => Pages\EditCmsCategory::route('/{record}/edit'),
        ];
    }
}
