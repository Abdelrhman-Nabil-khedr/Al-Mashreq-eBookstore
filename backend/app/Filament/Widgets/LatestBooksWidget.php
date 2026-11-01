<?php

namespace App\Filament\Widgets;

use App\Models\Book;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBooksWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'أحدث الكتب المضافة إلى الكتالوج';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Book::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')
                    ->label('الغلاف')
                    ->defaultImageUrl(fn (Book $record): string => $record->cover_url)
                    ->square(),

                Tables\Columns\TextColumn::make('isbn')
                    ->label('الرقم الدولي (ISBN)')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الكتاب')
                    ->limit(50)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('المؤلف')
                    ->default('—'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('التصنيف الرئيسي')
                    ->badge()
                    ->color('info')
                    ->default('—'),

                Tables\Columns\TextColumn::make('language')
                    ->label('اللغة')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
