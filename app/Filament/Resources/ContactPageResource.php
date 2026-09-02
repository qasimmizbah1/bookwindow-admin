<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactPageResource\Pages;
use App\Filament\Resources\ContactPageResource\RelationManagers;
use App\Models\ContactPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Textarea;

class ContactPageResource extends Resource
{
    protected static ?string $model = ContactPage::class;
     protected static ?string $navigationGroup = 'Pages';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Update Information')
                ->schema([
                    Forms\Components\Placeholder::make('last_updated_at')
                        ->label('Last Updated')
                        ->content(function (?ContactPage $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?ContactPage $record) {
                            return $record?->updatedBy?->name ?? 'Not available';
                        }),
                ])
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'edit'),

                Forms\Components\Section::make('Contact Sections')
                    ->schema([

                        Forms\Components\TextInput::make('con_title')
                            ->label('Sub Title')
                            ->columnSpanFull(),
                       
                        Forms\Components\Textarea::make('con_phone')
                            ->label('Phone'),
                        Forms\Components\Textarea::make('con_email')
                            ->label('E-mail'),

                         Forms\Components\RichEditor::make('con_address')
                            ->label('Address'),    
                        Forms\Components\Textarea::make('con_map')
                            ->label('Banner Description')
                             ,

                    ]) ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListContactPages::route('/'),
            'create' => Pages\CreateContactPage::route('/create'),
            'edit' => Pages\EditContactPage::route('/{record}/edit'),
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
