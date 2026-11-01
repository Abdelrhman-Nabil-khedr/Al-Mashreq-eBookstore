<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthorResource\Pages;
use App\Filament\Resources\AuthorResource\RelationManagers;
use App\Models\Author;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'الكتالوج والمحتوى';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'مؤلف';

    protected static ?string $pluralModelLabel = 'المؤلفون';

    protected static ?string $navigationLabel = 'المؤلفون';

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
                    ->unique(Author::class, 'slug', ignoreRecord: true),
                Forms\Components\RichEditor::make('biography')
                    ->label('النبذة')
                    ->maxLength(65535)
                    ->columnSpanFull(),
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
                Tables\Filters\TernaryFilter::make('has_biography')
                    ->label('النبذة')
                    ->placeholder('جميع المؤلفين')
                    ->trueLabel('مع نبذة')
                    ->falseLabel('بدون نبذة')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('biography')->where('biography', '!=', ''),
                        false: fn (Builder $query) => $query->whereNull('biography')->orWhere('biography', ''),
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
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'view' => Pages\ViewAuthor::route('/{record}'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
