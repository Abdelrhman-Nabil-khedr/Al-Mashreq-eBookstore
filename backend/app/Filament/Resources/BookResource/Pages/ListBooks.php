<?php

namespace App\Filament\Resources\BookResource\Pages;

use App\Filament\Resources\BookResource;
use App\Models\Book;
use App\Services\BookExcelImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use FilesystemIterator;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ListBooks extends ListRecords
{
    protected static string $resource = BookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة كتاب جديد'),

            // 1. Custom OpenSpout / SimpleXLS Excel Bulk Importer (Green Button)
            Actions\Action::make('excelBulkImport')
                ->label('استيراد ملف Excel (.xlsx, .xls)')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->modalHeading('استيراد جماعي لكتب الإكسيل')
                ->modalDescription('ارفع ملف Excel أو CSV ليتم استيراد الكتب وتحديث البيانات تلقائياً بالاعتماد على رقم ISBN، مع إنشاء أو ربط المؤلفين ودور النشر والتصنيفات.')
                ->form([
                    FileUpload::make('excel_file')
                        ->label('ملف الإكسيل (.xlsx, .xls أو .csv)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->disk('local')
                        ->directory('temp-imports')
                        ->preserveFilenames()
                        ->required()
                        ->helperText('يدعم نموذج العملاء الجديد Template.xls وتجاوز أسطر الإرشادات تلقائياً.'),
                ])
                ->action(function (array $data, BookExcelImportService $service): void {
                    $relativePath = $data['excel_file'] ?? null;
                    if (is_array($relativePath)) {
                        $relativePath = array_values($relativePath)[0] ?? null;
                    }
                    if (!$relativePath) {
                        return;
                    }

                    $fullPath = Storage::disk('local')->path($relativePath);
                    if (!file_exists($fullPath)) {
                        Notification::make()
                            ->danger()
                            ->title('خطأ في قراءة الملف')
                            ->body('تعذر العثور على الملف المرفوع في الخادم.')
                            ->send();
                        return;
                    }

                    // Server-side extension validation (allow only xlsx, xls, csv, txt)
                    $allowedExtensions = ['xlsx', 'xls', 'csv', 'txt'];
                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExtensions, true)) {
                        @unlink($fullPath);
                        Notification::make()
                            ->danger()
                            ->title('نوع الملف غير مدعوم')
                            ->body('يُسمح فقط برفع ملفات Excel و CSV (.xlsx, .xls, .csv, .txt).')
                            ->send();
                        return;
                    }

                    $result = $service->import($fullPath);

                    // Clean up temp file
                    @unlink($fullPath);

                    $hasErrors = !empty($result['errors']);
                    $hasSuccesses = (($result['created'] ?? 0) > 0 || ($result['updated'] ?? 0) > 0);

                    $msgParts = [];
                    $msgParts[] = "تمت معالجة {$result['total']} كتاب: ({$result['created']} كتاب جديد، {$result['updated']} كتاب تم تحديثه).";

                    if ($hasErrors) {
                        $errorCount = count($result['errors']);
                        $msgParts[] = "<strong style='color:#e11d48;'>حدث خطأ في {$errorCount} سطر:</strong>";

                        $displayErrors = array_slice($result['errors'], 0, 15);
                        foreach ($displayErrors as $err) {
                            $msgParts[] = "• " . e($err);
                        }
                        if ($errorCount > 15) {
                            $msgParts[] = "... و" . ($errorCount - 15) . " خطأ إضافي (راجع سجل النظام laravel.log).";
                        }
                    }

                    $bodyHtml = new HtmlString(implode('<br>', $msgParts));

                    $notification = Notification::make();

                    if (!$hasSuccesses && $hasErrors) {
                        $notification->danger()->title('فشل استيراد ملف الإكسيل');
                    } elseif ($hasErrors) {
                        $notification->warning()->title('اكتمل الاستيراد مع وجود بعض الأخطاء');
                    } else {
                        $notification->success()->title('اكتمل استيراد ملف الإكسيل بنجاح');
                    }

                    $notification
                        ->body($bodyHtml)
                        ->duration($hasErrors ? 20000 : 6000)
                        ->send();
                }),

            // 2. ZIP Image Bulk Uploader
            Actions\Action::make('zipCoverUpload')
                ->label('رفع أغلفة مجمعة (ZIP)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('info')
                ->modalHeading('رفع حزمة أغلفة مضغوطة (ZIP)')
                ->modalDescription('ارفع ملف ZIP يحتوي على صور أغلفة الكتب. سيتم مطابقة اسم كل صورة مع رقم الردمك (ISBN) الخاص بالكتاب ونقلها إلى مجلد الأغلفة وتحديث السجل تلقائياً.')
                ->form([
                    FileUpload::make('zip_file')
                        ->label('ملف ZIP للأغلفة')
                        ->acceptedFileTypes([
                            'application/zip',
                            'application/x-zip-compressed',
                            'multipart/x-zip',
                        ])
                        ->disk('local')
                        ->directory('temp-zips')
                        ->preserveFilenames()
                        ->required()
                        ->helperText('يجب أن تكون أسماء الصور مطابقة لرقم الردمك (مثال: 9781285604374.jpg أو .png أو .webp)'),
                ])
                ->action(function (array $data): void {
                    $relativePath = $data['zip_file'] ?? null;
                    if (is_array($relativePath)) {
                        $relativePath = array_values($relativePath)[0] ?? null;
                    }
                    if (!$relativePath) {
                        return;
                    }

                    $zipPath = Storage::disk('local')->path($relativePath);
                    if (!file_exists($zipPath)) {
                        Notification::make()
                            ->danger()
                            ->title('خطأ في العثور على الملف')
                            ->body('تعذر العثور على ملف ZIP المرفوع على الخادم.')
                            ->send();
                        return;
                    }

                    $zip = new ZipArchive();
                    $openResult = $zip->open($zipPath);
                    if ($openResult !== true) {
                        Notification::make()
                            ->danger()
                            ->title('خطأ في فتح الأرشيف')
                            ->body('تعذر فك ضغط ملف ZIP المرفوع (رمز الخطأ: ' . $openResult . ')')
                            ->send();
                        @unlink($zipPath);
                        return;
                    }

                    $tempExtractDir = storage_path('app/temp-extract-' . uniqid());
                    if (!is_dir($tempExtractDir)) {
                        mkdir($tempExtractDir, 0755, true);
                    }

                    // Validate all entries before extraction to prevent Zip Slip attacks
                    $isSafe = true;
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entryName = $zip->getNameIndex($i);
                        if ($entryName === false) {
                            continue;
                        }

                        // Check for path traversal or absolute path injection
                        if (
                            str_contains($entryName, '..') ||
                            str_starts_with($entryName, '/') ||
                            str_starts_with($entryName, '\\') ||
                            preg_match('/^[a-zA-Z]:/', $entryName)
                        ) {
                            $isSafe = false;
                            break;
                        }
                    }

                    if (!$isSafe) {
                        $zip->close();
                        @unlink($zipPath);
                        @rmdir($tempExtractDir);
                        Notification::make()
                            ->danger()
                            ->title('ملف ZIP مشبوه')
                            ->body('تم رفض الملف: يحتوي على مسارات مشبوهة أو محاولة اختراق للمسارات (Zip Slip Attack).')
                            ->send();
                        return;
                    }

                    $zip->extractTo($tempExtractDir);
                    $zip->close();
                    @unlink($zipPath);

                    $coversDir = storage_path('app/public/covers');
                    if (!is_dir($coversDir)) {
                        mkdir($coversDir, 0755, true);
                    }

                    $matchedCount = 0;
                    $unmatchedFiles = [];
                    $supportedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tempExtractDir, FilesystemIterator::SKIP_DOTS)
                    );

                    foreach ($iterator as $file) {
                        if ($file->isDir()) {
                            continue;
                        }

                        $filename = $file->getFilename();
                        $ext = strtolower($file->getExtension());

                        // Skip macOS metadata and dotfiles
                        if (str_starts_with($filename, '.') || str_starts_with($filename, '__MACOSX')) {
                            continue;
                        }

                        if (!in_array($ext, $supportedExtensions, true)) {
                            continue;
                        }

                        $rawIsbn = pathinfo($filename, PATHINFO_FILENAME);
                        $isbn = trim(str_replace([' ', '-'], '', $rawIsbn));

                        $book = Book::where('isbn', $isbn)->first();

                        if ($book) {
                            $destFilename = "{$isbn}.{$ext}";
                            $destPath = $coversDir . DIRECTORY_SEPARATOR . $destFilename;

                            copy($file->getRealPath(), $destPath);

                            $coverPath = "covers/{$destFilename}";
                            $book->cover_path = $coverPath;
                            $book->cover = $coverPath;
                            $book->save();

                            $matchedCount++;
                        } else {
                            $unmatchedFiles[] = $filename;
                        }
                    }

                    // Recursively clean up temp directory
                    $cleanIterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tempExtractDir, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($cleanIterator as $item) {
                        if ($item->isDir()) {
                            @rmdir($item->getRealPath());
                        } else {
                            @unlink($item->getRealPath());
                        }
                    }
                    @rmdir($tempExtractDir);

                    $body = "تمت مطابقة وحفظ {$matchedCount} غلاف كتاب بنجاح في مجلد الأغلفة.";
                    if (count($unmatchedFiles) > 0) {
                        $preview = implode(', ', array_slice($unmatchedFiles, 0, 3));
                        $body .= ' (' . count($unmatchedFiles) . ' ملف لم يتم العثور على كتب مطابقة لـ ISBN مثل: ' . $preview . ')';
                    }

                    Notification::make()
                        ->success()
                        ->title('اكتملت معالجة الأغلفة المضغوطة')
                        ->body($body)
                        ->send();
                }),
        ];
    }
}
