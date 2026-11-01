<?php

namespace App\Filament\Widgets;

use App\Models\Author;
use App\Models\BlogPost;
use App\Models\Book;
use App\Models\Category;
use App\Models\ContactSubmission;
use App\Models\Page;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Get the statistics cards for the dashboard.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $totalBooks = Book::count();
        $publishedBooks = Book::published()->count();
        $totalAuthors = Author::count();
        $totalCategories = Category::count();
        $publishedPosts = BlogPost::published()->count();
        $totalPages = Page::published()->count();

        return [
            Stat::make('إجمالي الكتب', number_format($totalBooks))
                ->description("{$publishedBooks} كتاب معروض للزوار")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([2400, 2550, 2680, 2750, 2800, $totalBooks]),

            Stat::make('المؤلفون والكتّاب', number_format($totalAuthors))
                ->description('مؤلفون في قاعدة البيانات')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('أقسام وتصنيفات الكتب', number_format($totalCategories))
                ->description('تصنيفات رئيسية وفرعية')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),

            Stat::make('مقالات المدونة', number_format($publishedPosts))
                ->description("{$publishedPosts} مقال منشور للجمهور")
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('warning'),

            Stat::make('الصفحات الثابتة', number_format($totalPages))
                ->description('من نحن والرؤية والمهمة')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('secondary'),
        ];
    }
}
