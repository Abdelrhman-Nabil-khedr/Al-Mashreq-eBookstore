<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'من نحن',
                'subtitle' => 'بدأت مكتبة المشرق الإلكترونية نشاطها بجمع وتحرير عدد كبير من كتب التراث لكبار الكُتّاب العرب الذين أثروا بأفكارهم في المجتمع الثقافي العربي في الماضي القريب.',
                'content' => '<h2>ماذا نقدم؟</h2>
<p>نعمل على نشر أكبر مكتبة عربية تضم أهم كتب التراث العربي بعد إعادة إنتاجها في شكل رقمي عصري متميز، لبناء أرشيف رقمي كامل يحافظ على تلك الكنوز من الاندثار.</p>

<h2>ترجمات عالمية وكتب للأطفال</h2>
<p>توجهت المكتبة أيضًا إلى نشر عدد كبير من الكتب المترجمة لكبار المؤلفين العالميين والتي ترجمها عدد من أعظم الأدباء العرب، وتمت إعادة صياغة وتحرير تلك المؤلفات لتتماشى مع توقعات القارئ العصري. كما تضم المكتبة عددًا كبيرًا من كتب الأطفال واليافعين إلى أرشيفها سواء المترجمة منها أو المؤلفة.</p>

<h2>شراكات ونشر</h2>
<p>تهدف المكتبة إلى إتاحة سهولة الوصول إلى كل من التراث العربي والمحتوى المتميز، لذا قمنا ببناء شراكة مثمرة مع عدد كبير من منصات نشر الكتب الإلكترونية العالمية والإقليمية.</p>',
                'title_en' => 'About Us',
                'subtitle_en' => 'Al-Mashreq eBookstore began its journey by collecting and editing Arabic heritage literature from pioneering Arab authors whose thoughts enriched cultural society.',
                'content_en' => '<h2>What We Offer</h2>
<p>We publish one of the largest Arabic digital libraries of heritage books in modern, high-quality formats, building a permanent archive to preserve these treasures.</p>

<h2>World Translations & Young Reader Books</h2>
<p>We also curate acclaimed international translations translated by prominent Arab scholars, re-edited for modern readers, alongside an extensive catalog for young adults and children.</p>

<h2>Partnerships & Global Distribution</h2>
<p>We aim to provide effortless access to Arabic heritage and high-caliber digital content, forging successful distribution partnerships across leading global and regional eBook platforms.</p>',
                'meta_title' => 'من نحن - مكتبة المشرق الإلكترونية',
                'meta_description' => 'تعرف على قصة ورسالة مكتبة المشرق الإلكترونية في إحياء التراث العربي ونشر الثقافة الرقمية.',
                'is_published' => true,
            ],
            [
                'slug' => 'vision',
                'title' => 'الرؤية والمهمة',
                'subtitle' => 'نعمل على إحياء المجتمع الثقافي العربي عالميًا عبر محتوى رقمي يليق بالقارئ العصري ويحافظ على كنوز التراث من الاندثار.',
                'content' => '<h2>الرؤية</h2>
<p>نرغب في إحياء المجتمع الثقافي العربي في جميع أنحاء العالم، وأن نوفر المحتوى الرقمي الذي يساهم في ذلك.</p>

<h2>المهمة</h2>
<p>إحياء كتب التراث في شكل جديد يتناسب مع القارئ العصري، ثم إضافة محتوى رقمي متميز لكافة فئات المجتمع.</p>

<h2>قيمنا</h2>
<ul>
  <li><strong>الاحترافية:</strong> جودة عالية في الإعداد والتحرير والعرض الرقمي.</li>
  <li><strong>التناغم:</strong> تجربة قراءة سلسة تربط التراث بروح العصر.</li>
  <li><strong>التواصل:</strong> شراكات ودعم مستمر لخدمة القارئ والمنصات.</li>
</ul>',
                'title_en' => 'Vision & Mission',
                'subtitle_en' => 'Reviving Arabic cultural society globally through modern digital content fitting the modern reader and preserving heritage treasures.',
                'content_en' => '<h2>Our Vision</h2>
<p>We aspire to revitalize the Arab cultural community worldwide by providing rich, accessible digital literature that preserves our cultural identity.</p>

<h2>Our Mission</h2>
<p>Reviving classical Arabic books in contemporary formats tailored for modern readers, while expanding our digital catalog across diverse knowledge domains.</p>

<h2>Our Core Values</h2>
<ul>
  <li><strong>Professionalism:</strong> Highest standards in digitization, editing, and typography.</li>
  <li><strong>Harmony:</strong> Seamless reading experience connecting timeless heritage to the modern age.</li>
  <li><strong>Connectivity:</strong> Enduring partnerships and dedicated reader support across platforms.</li>
</ul>',
                'meta_title' => 'الرؤية والمهمة - مكتبة المشرق الإلكترونية',
                'meta_description' => 'رؤية ورسالة وقيم مكتبة المشرق الإلكترونية نحو إثراء المحتوى الثقافي العربي رقميًا.',
                'is_published' => true,
            ],
        ];

        foreach ($pages as $pageData) {
            Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}
