<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Services\BookExcelImportService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BookExcelImportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $testIsbn = '9789999000001';
        $book = Book::where('isbn', $testIsbn)->first();
        if ($book) {
            $book->keywords()->delete();
            $book->platformLinks()->delete();
            $book->delete();
        }

        parent::tearDown();
    }

    public function test_importer_parses_arabic_headers_and_skips_instruction_row(): void
    {
        $testIsbn = '9789999000001';
        Book::where('isbn', $testIsbn)->delete();

        // Prepare CSV with exact client Template headers, instruction row at index 1, and data row
        $headers = [
            'Book ISBN',
            'العنوان',
            'العنوان الفرعي',
            'المؤلف',
            'الرسام',
            'المترجم',
            'المحرر',
            'الناشر',
            'اسم السلسلة',
            'الموقع في السلسلة',
            'Book Bisac Code',
            'WGS code',
            'Thema Code',
            'Catalog code',
            'اللغة الأصلية',
            'لغة الكتاب',
            'تاريخ النشر',
            'كلمات مفتاحية',
            'نبذة',
            'الفئة العمرية',
            'عدد الصفحات',
            'فئة 1',
            'فئة 2',
            'فئة 3',
            'فئة 4',
            'Subject 1',
            'Subject 2',
            'Subject 3',
            'Subject 4',
            'صيغة',
            'نبذة عن المؤلف',
        ];

        $instructionRow = [
            '13 digits (no dashes od spaces)',
            'Text in Arabic',
            'Text in Arabic',
            'List of the registered Authors',
            'Text in Arabic',
            'Text in Arabic',
            'Text in Arabic',
            'Text in Arabic',
            'Text in Arabic',
            'number 2 digits (01, 02, 03, 12, 99)',
            'Text and numbers in English',
            'Text and numbers in English',
            'Text and numbers in English',
            'Text and numbers in English',
            'languages list, English in the top',
            'languages list, Arabic in the top',
            'dd/mm/yyyy',
            'Text in Arabic',
            'Text in Arabic',
            'numbers and dashes',
            'number in English',
            'Text in Arabic',
            'Text in Arabic',
            'Text in Arabic',
            'Text in Arabic',
            'Text in English',
            'Text in English',
            'Text in English',
            'Text in English',
            'List (eBook, Audio)',
            'Text in Arabic',
        ];

        $dataRow = [
            $testIsbn,
            'كتاب الاختبار العربي',
            'دراسة نقدية موسعة',
            'د. نجيب محفوظ',
            'مصطفى الرسام',
            'منير المترجم',
            'فؤاد المحرر',
            'دار الشروق الحديثة',
            'سلسلة نوبل العربية',
            '01',
            'FIC002000',
            '110',
            'FB',
            'CAT-TEST-01',
            'English',
            'Arabic',
            '25/12/2024',
            'أدب, رواية, تاريخ',
            'نبذة توضيحية شاملة عن الرواية وقيمتها الأدبية',
            'للبالغين',
            '350',
            'أدب وروايات',
            'روايات كلاسيكية',
            '',
            '',
            '',
            '',
            '',
            '',
            'eBook',
            'روائي عربي حائز على جائزة نوبل للآداب',
        ];

        $tempFile = storage_path('app/test_arabic_import_' . uniqid() . '.csv');

        $fp = fopen($tempFile, 'w');
        fputs($fp, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($fp, $headers);
        fputcsv($fp, $instructionRow);
        fputcsv($fp, $dataRow);
        fclose($fp);

        $service = new BookExcelImportService();
        $result = $service->import($tempFile);

        @unlink($tempFile);

        $this->assertEquals(1, $result['total'], 'Expected exactly 1 valid book row (instruction row must be skipped).');
        $this->assertEquals(1, $result['created']);
        $this->assertEmpty($result['errors']);

        // Assert database attributes
        $book = Book::where('isbn', $testIsbn)->first();
        $this->assertNotNull($book);
        $this->assertEquals('كتاب الاختبار العربي', $book->title);
        $this->assertEquals('دراسة نقدية موسعة', $book->subtitle);
        $this->assertEquals('مصطفى الرسام', $book->illustrator);
        $this->assertEquals('منير المترجم', $book->translator);
        $this->assertEquals('فؤاد المحرر', $book->editor);
        $this->assertEquals('سلسلة نوبل العربية', $book->series);
        $this->assertEquals('01', $book->series_position);
        $this->assertEquals('FIC002000', $book->bisac_code);
        $this->assertEquals('110', $book->wgs_code);
        $this->assertEquals('FB', $book->thema_code);
        $this->assertEquals('CAT-TEST-01', $book->catalog_code);
        $this->assertEquals('English', $book->original_language);
        $this->assertEquals('Arabic', $book->language);
        $this->assertEquals('2024-12-25', $book->publication_date->format('Y-m-d'));
        $this->assertEquals('eBook', $book->format);
        $this->assertEquals('روائي عربي حائز على جائزة نوبل للآداب', $book->author_bio);
        $this->assertEquals('نبذة توضيحية شاملة عن الرواية وقيمتها الأدبية', $book->synopsis);
        $this->assertEquals(350, $book->pages);
        $this->assertEquals('للبالغين', $book->age_range);

        // Assert Relationships
        $this->assertNotNull($book->author);
        $this->assertEquals('د. نجيب محفوظ', $book->author->name);

        $this->assertNotNull($book->publisher);
        $this->assertEquals('دار الشروق الحديثة', $book->publisher->name);

        $this->assertNotNull($book->category);
        $this->assertEquals('أدب وروايات', $book->category->name);

        $this->assertNotNull($book->subCategory);
        $this->assertEquals('روايات كلاسيكية', $book->subCategory->name);

        // Assert keywords
        $this->assertGreaterThanOrEqual(2, $book->keywords()->count());
    }
}
