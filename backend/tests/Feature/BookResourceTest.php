<?php

namespace Tests\Feature;

use App\Filament\Resources\BookResource\Pages\CreateBook;
use App\Models\Book;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class BookResourceTest extends TestCase
{
    public function test_admin_can_render_books_index_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/books');

        $response->assertSuccessful();
    }

    public function test_admin_can_render_book_create_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/books/create');

        $response->assertSuccessful();
    }

    public function test_admin_can_create_book_with_partner_links(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin);

        $testIsbn = '9789999999999';
        Book::where('isbn', $testIsbn)->delete();

        Livewire::test(CreateBook::class)
            ->fillForm([
                'title' => 'كتاب تجريبي لاختبار الروابط',
                'isbn' => $testIsbn,
                'language' => 'Arabic',
                'pages' => 250,
                'is_published' => true,
                'is_featured' => false,
                'platformLinks' => [
                    [
                        'platform_name' => 'جرير',
                        'url' => 'https://www.jarir.com/sa-ar/test-book.html',
                        'sort_order' => 1,
                    ],
                    [
                        'platform_name' => 'أمازون',
                        'url' => 'https://www.amazon.sa/dp/testbook',
                        'sort_order' => 2,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $book = Book::where('isbn', $testIsbn)->first();
        $this->assertNotNull($book);
        $this->assertEquals('كتاب تجريبي لاختبار الروابط', $book->title);
        $this->assertEquals(2, $book->platformLinks()->count());

        // Clean up test record
        $book->platformLinks()->delete();
        $book->delete();
    }

    public function test_list_books_header_actions_are_clean(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin);

        Livewire::test(\App\Filament\Resources\BookResource\Pages\ListBooks::class)
            ->assertActionExists('excelBulkImport')
            ->assertActionExists('zipCoverUpload')
            ->assertActionDoesNotExist('import');
    }

    public function test_zip_cover_upload_action_matches_isbn_and_updates_cover(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin);

        $testIsbn = '9789999887766';
        Book::where('isbn', $testIsbn)->delete();

        $book = Book::create([
            'isbn' => $testIsbn,
            'title' => 'كتاب لاختبار رفع الغلاف المضغوط',
            'language' => 'Arabic',
        ]);

        // Create a temporary ZIP file containing {isbn}.jpg
        $zipPath = storage_path('app/temp_test_covers_' . uniqid() . '.zip');
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        // Create dummy 1-pixel or text content for dummy image
        $zip->addFromString("{$testIsbn}.jpg", "fake-image-binary-data");
        $zip->close();

        // Put the zip into local disk storage path relative to disk
        $relativeZipPath = 'temp-zips/' . basename($zipPath);
        \Illuminate\Support\Facades\Storage::disk('local')->put($relativeZipPath, file_get_contents($zipPath));
        @unlink($zipPath);

        // Call the action via Livewire
        Livewire::test(\App\Filament\Resources\BookResource\Pages\ListBooks::class)
            ->callAction('zipCoverUpload', [
                'zip_file' => [$relativeZipPath],
            ])
            ->assertHasNoActionErrors();

        // Check that cover_path and cover were updated in DB
        $book->refresh();
        $this->assertEquals("covers/{$testIsbn}.jpg", $book->cover_path);
        $this->assertEquals("covers/{$testIsbn}.jpg", $book->cover);

        // Check file exists in covers directory
        $coverFile = storage_path("app/public/covers/{$testIsbn}.jpg");
        $this->assertFileExists($coverFile);

        // Clean up
        @unlink($coverFile);
        $book->delete();
    }
}
