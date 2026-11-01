<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PublisherResource\Pages;
use App\Filament\Resources\PublisherResource\RelationManagers;
use App\Models\Publisher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PublisherResource extends Resource
{
    protected static ?string $model = Publisher::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'الكتالوج والمحتوى';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'دار نشر';

    protected static ?string $pluralModelLabel = 'دور النشر';

    protected static ?string $navigationLabel = 'دور النشر';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() ?? false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(500)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state) ?: urlencode($state ?? '')) : null),
                Forms\Components\TextInput::make('slug')
                    ->label('الرابط اللطيف')
                    ->required()
                    ->maxLength(500)
                    ->unique(Publisher::class, 'slug', ignoreRecord: true),
                Forms\Components\TextInput::make('url')
                    ->label('الموقع الإلكتروني')
                    ->url()
                    ->maxLength(1000)
                    ->placeholder('https://example.com'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('الرابط اللطيف')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('الموقع الإلكتروني')
                    ->url(fn ($record) => $record->url, shouldOpenInNewTab: true)
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('books_count')
                    ->label('عدد الكتب')
                    ->counts('books')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_url')
                    ->label('الموقع الإلكتروني')
                    ->placeholder('جميع دور النشر')
                    ->trueLabel('لديه موقع إلكتروني')
                    ->falseLabel('بدون موقع إلكتروني')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('url')->where('url', '!=', ''),
                        false: fn (Builder $query) => $query->whereNull('url')->orWhere('url', ''),
                    ),
                Tables\Filters\Filter::make('has_books')
                    ->label('لديه كتب منشورة')
                    ->query(fn (Builder $query) => $query->has('books')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPublishers::route('/'),
            'create' => Pages\CreatePublisher::route('/create'),
            'view' => Pages\ViewPublisher::route('/{record}'),
            'edit' => Pages\EditPublisher::route('/{record}/edit'),
        ];
    }
}
