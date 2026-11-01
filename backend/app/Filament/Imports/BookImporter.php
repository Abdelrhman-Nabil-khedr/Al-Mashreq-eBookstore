<?php

namespace App\Filament\Imports;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class BookImporter extends Importer
{
    protected static ?string $model = Book::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('isbn')
                ->label('الرقم الدولي المعياري (ISBN)')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:50']),

            ImportColumn::make('title')
                ->label('عنوان الكتاب (Title)')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:1000']),

            ImportColumn::make('subtitle')
                ->label('العنوان الفرعي (Subtitle)')
                ->rules(['nullable', 'string', 'max:1000']),

            ImportColumn::make('author')
                ->label('المؤلف (Author)')
                ->relationship(resolveUsing: function (?string $state): ?Author {
                    $name = trim((string) $state);
                    if (blank($name)) {
                        return null;
                    }
                    return Author::firstOrCreate(
                        ['name' => $name],
                        ['slug' => Str::slug($name) ?: ('author-' . Str::random(6))]
                    );
                }),

            ImportColumn::make('publisher')
                ->label('دار النشر (Publisher)')
                ->relationship(resolveUsing: function (?string $state): ?Publisher {
                    $name = trim((string) $state);
                    if (blank($name)) {
                        return null;
                    }
                    return Publisher::firstOrCreate(
                        ['name' => $name],
                        ['slug' => Str::slug($name) ?: ('publisher-' . Str::random(6))]
                    );
                }),

            ImportColumn::make('category')
                ->label('التصنيف الرئيسي (Category)')
                ->relationship(resolveUsing: function (?string $state): ?Category {
                    $name = trim((string) $state);
                    if (blank($name)) {
                        return null;
                    }
                    return Category::firstOrCreate(
                        ['name' => $name, 'parent_id' => null],
                        ['slug' => Str::slug($name) ?: ('cat-' . Str::random(6))]
                    );
                }),

            ImportColumn::make('subCategory')
                ->label('التصنيف الفرعي (Subcategory)')
                ->relationship(resolveUsing: function (?string $state): ?Category {
                    $name = trim((string) $state);
                    if (blank($name)) {
                        return null;
                    }
                    return Category::firstOrCreate(
                        ['name' => $name],
                        ['slug' => Str::slug($name) ?: ('subcat-' . Str::random(6))]
                    );
                }),

            ImportColumn::make('language')
                ->label('اللغة (Language)')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('pages')
                ->label('عدد الصفحات (Pages)')
                ->numeric()
                ->rules(['nullable', 'integer']),

            ImportColumn::make('series')
                ->label('اسم السلسلة (Series)')
                ->rules(['nullable', 'string', 'max:500']),

            ImportColumn::make('age_range')
                ->label('الفئة العمرية (Age Range)')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('synopsis')
                ->label('الملخص / النبذة (Synopsis)')
                ->rules(['nullable', 'string']),

            ImportColumn::make('contributor_name')
                ->label('المشارك / المترجم (Contributor)')
                ->rules(['nullable', 'string', 'max:500']),

            ImportColumn::make('contributor_role')
                ->label('صفة المشارك (Contributor Role)')
                ->rules(['nullable', 'string', 'max:100']),

            ImportColumn::make('cover_path')
                ->label('مسار الغلاف (Cover Path)')
                ->rules(['nullable', 'string', 'max:500']),

            ImportColumn::make('is_published')
                ->label('منشور في الكتالوج')
                ->boolean()
                ->rules(['nullable', 'boolean']),

            ImportColumn::make('is_featured')
                ->label('كتاب مميز')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    /**
     * Resolve existing record by ISBN or instantiate a new model.
     * Uses ISBN as the unique identifier for upserting.
     */
    public function resolveRecord(): ?Book
    {
        $isbn = trim((string) ($this->data['isbn'] ?? ''));
        if (empty($isbn)) {
            return null;
        }

        return Book::firstOrNew([
            'isbn' => $isbn,
        ]);
    }

    /**
     * Hook before saving to automatically resolve relationships if provided
     * by name or fallback columns, and assign sensible defaults.
     */
    public function beforeSave(): void
    {
        // 1. Resolve Author
        if (empty($this->record->author_id)) {
            $authorName = trim((string) (
                $this->data['author'] 
                ?? $this->originalData['author'] 
                ?? $this->originalData['Author'] 
                ?? $this->originalData['المؤلف'] 
                ?? ''
            ));

            if (filled($authorName)) {
                $author = Author::firstOrCreate(
                    ['name' => $authorName],
                    ['slug' => Str::slug($authorName) ?: ('author-' . Str::random(6))]
                );
                $this->record->author_id = $author->id;
            }
        }

        // 2. Resolve Publisher
        if (empty($this->record->publisher_id)) {
            $pubName = trim((string) (
                $this->data['publisher'] 
                ?? $this->originalData['publisher'] 
                ?? $this->originalData['Publisher'] 
                ?? $this->originalData['دار النشر'] 
                ?? ''
            ));

            if (filled($pubName)) {
                $publisher = Publisher::firstOrCreate(
                    ['name' => $pubName],
                    ['slug' => Str::slug($pubName) ?: ('publisher-' . Str::random(6))]
                );
                $this->record->publisher_id = $publisher->id;
            }
        }

        // 3. Resolve Main Category
        if (empty($this->record->category_id)) {
            $catName = trim((string) (
                $this->data['category'] 
                ?? $this->originalData['category'] 
                ?? $this->originalData['Category'] 
                ?? $this->originalData['CategoryMain'] 
                ?? $this->originalData['التصنيف'] 
                ?? $this->originalData['التصنيف الرئيسي'] 
                ?? ''
            ));

            if (filled($catName)) {
                $category = Category::firstOrCreate(
                    ['name' => $catName, 'parent_id' => null],
                    ['slug' => Str::slug($catName) ?: ('cat-' . Str::random(6))]
                );
                $this->record->category_id = $category->id;
            }
        }

        // 4. Resolve Subcategory
        if (empty($this->record->sub_category_id)) {
            $subCatName = trim((string) (
                $this->data['subCategory'] 
                ?? $this->originalData['subCategory'] 
                ?? $this->originalData['sub_category'] 
                ?? $this->originalData['Subject_2'] 
                ?? $this->originalData['التصنيف الفرعي'] 
                ?? ''
            ));

            if (filled($subCatName)) {
                $subCat = Category::firstOrCreate(
                    ['name' => $subCatName, 'parent_id' => $this->record->category_id],
                    ['slug' => Str::slug($subCatName) ?: ('subcat-' . Str::random(6))]
                );
                $this->record->sub_category_id = $subCat->id;
            }
        }

        // Defaults
        if ($this->record->is_published === null) {
            $this->record->is_published = true;
        }

        if ($this->record->is_featured === null) {
            $this->record->is_featured = false;
        }

        if (empty($this->record->language)) {
            $this->record->language = 'Arabic';
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'اكتمل استيراد الكتب بنجاح. تم استيراد ' . number_format($import->successful_rows) . ' كتاب.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' وتعذر استيراد ' . number_format($failedRowsCount) . ' سطر.';
        }

        return $body;
    }
}
