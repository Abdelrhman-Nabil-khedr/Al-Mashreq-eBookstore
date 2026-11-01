<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookResource\Pages;
use App\Models\Book;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'الكتالوج والمحتوى';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'كتاب';

    protected static ?string $pluralModelLabel = 'الكتب';

    protected static ?string $navigationLabel = 'الكتب';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الكتاب الأساسية')
                    ->description('العناوين، رمز الردمك، اللغة، وتفاصيل الطبعة')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الكتاب')
                            ->placeholder('أدخل عنوان الكتاب كاملاً...')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        TextInput::make('subtitle')
                            ->label('العنوان الفرعي')
                            ->placeholder('العنوان التوضيحي أو الفرعي إن وجد...')
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('isbn')
                                    ->label('الرقم الدولي المعياري (ISBN)')
                                    ->placeholder('مثال: 9789779075433')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    ->helperText('يُستخدم لربط الكتب والبحث المباشر'),

                                TextInput::make('pages')
                                    ->label('عدد الصفحات')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('مثال: 320'),

                                DatePicker::make('publication_date')
                                    ->label('تاريخ النشر')
                                    ->placeholder('اختر تاريخ النشر...')
                                    ->displayFormat('d/m/Y'),

                                Select::make('format')
                                    ->label('صيغة الكتاب')
                                    ->options([
                                        'ورقي' => 'ورقي (Print)',
                                        'eBook' => 'كتاب إلكتروني (eBook)',
                                        'Audio' => 'كتاب صوتي (Audio)',
                                        'مزدوج' => 'ورقي ورقمي',
                                    ])
                                    ->searchable()
                                    ->createOptionUsing(fn ($value) => $value)
                                    ->placeholder('اختر أو أضف صيغة...'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('language')
                                    ->label('لغة الكتاب')
                                    ->options([
                                        'Arabic' => 'العربية (Arabic)',
                                        'English' => 'الإنجليزية (English)',
                                        'French' => 'الفرنسية (French)',
                                        'German' => 'الألمانية (German)',
                                        'Spanish' => 'الإسبانية (Spanish)',
                                    ])
                                    ->default('Arabic')
                                    ->searchable()
                                    ->required(),

                                Select::make('original_language')
                                    ->label('اللغة الأصلية')
                                    ->options([
                                        'Arabic' => 'العربية (Arabic)',
                                        'English' => 'الإنجليزية (English)',
                                        'French' => 'الفرنسية (French)',
                                        'German' => 'الألمانية (German)',
                                        'Spanish' => 'الإسبانية (Spanish)',
                                        'Italian' => 'الإيطالية (Italian)',
                                        'Russian' => 'الروسية (Russian)',
                                    ])
                                    ->searchable()
                                    ->createOptionUsing(fn ($value) => $value)
                                    ->placeholder('اللغة الأصلية للعمل إن كان مترجماً...'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('series')
                                    ->label('اسم السلسلة / المجموعة')
                                    ->placeholder('اسم السلسلة الروائية أو العلمية...')
                                    ->maxLength(500),

                                TextInput::make('series_position')
                                    ->label('الموقع في السلسلة (الرقم / الجزء)')
                                    ->placeholder('مثال: 01 أو 02 أو الجزء الأول')
                                    ->maxLength(50),

                                Select::make('age_range')
                                    ->label('الفئة العمرية')
                                    ->options([
                                        'عام' => 'عام (كافة الأعمار)',
                                        'للبالغين' => 'للبالغين (18+)',
                                        '12-16 سنة' => 'يافعين (12-16 سنة)',
                                        '9-12 سنة' => 'أطفال (9-12 سنة)',
                                        '6-9 سنوات' => 'أطفال مبكر (6-9 سنوات)',
                                    ])
                                    ->searchable()
                                    ->createOptionUsing(fn ($value) => $value),
                            ]),
                    ]),

                Section::make('المؤلف، دار النشر، والتصنيفات')
                    ->description('تحديد المؤلف، دار النشر، التصنيفات الموضوعية، وفريق العمل')
                    ->icon('heroicon-o-tag')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('author_id')
                                    ->label('المؤلف')
                                    ->relationship('author', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('اسم المؤلف')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->helperText('اختر المؤلف أو أضف مؤلفاً جديداً'),

                                Select::make('publisher_id')
                                    ->label('دار النشر')
                                    ->relationship('publisher', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('اسم دار النشر')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->helperText('اختر دار النشر أو أنشئ جديدة'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('category_id')
                                    ->label('التصنيف الرئيسي')
                                    ->relationship('category', 'name', fn (Builder $query) => $query->whereNull('parent_id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->helperText('التصنيف العام (مثال: الأدب، العلوم، التاريخ)'),

                                Select::make('sub_category_id')
                                    ->label('التصنيف الفرعي')
                                    ->relationship('subCategory', 'name', function (Builder $query, Forms\Get $get) {
                                        $parentId = $get('category_id');
                                        if ($parentId) {
                                            return $query->where('parent_id', $parentId);
                                        }
                                        return $query->whereNotNull('parent_id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('التفرع المباشر تحت التصنيف المحدد'),
                            ]),

                        Fieldset::make('فريق العمل والمشاركون (Contributors & Team)')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('translator')
                                            ->label('المترجم')
                                            ->placeholder('اسم المترجم إن وجد...')
                                            ->maxLength(500),

                                        TextInput::make('editor')
                                            ->label('المحرر')
                                            ->placeholder('اسم المحرر إن وجد...')
                                            ->maxLength(500),

                                        TextInput::make('illustrator')
                                            ->label('الرسام')
                                            ->placeholder('اسم الرسام أو المصمم...')
                                            ->maxLength(500),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('contributor_name')
                                            ->label('مشارك آخر / محقق / مقدم')
                                            ->placeholder('اسم المشارك الإضافي...')
                                            ->maxLength(500),

                                        Select::make('contributor_role')
                                            ->label('صفة المشارك الإضافي')
                                            ->options([
                                                'تقديم ومراجعة' => 'تقديم ومراجعة (Preface/Review)',
                                                'محقق' => 'محقق ومراجع (Investigator)',
                                                'خطاط' => 'خطاط (Calligrapher)',
                                                'إشراف عام' => 'إشراف عام (General Supervisor)',
                                            ])
                                            ->searchable()
                                            ->createOptionUsing(fn ($value) => $value),
                                    ]),
                            ]),
                    ]),

                Section::make('رموز التصنيف والفهرسة المعيارية (Classification Codes)')
                    ->description('رموز التصنيف الببليوغرافي الدولي والمحلي')
                    ->icon('heroicon-o-qr-code')
                    ->collapsible()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('bisac_code')
                                    ->label('Book Bisac Code')
                                    ->placeholder('مثال: FIC000000')
                                    ->maxLength(100),

                                TextInput::make('wgs_code')
                                    ->label('WGS Code')
                                    ->placeholder('مثال: 110')
                                    ->maxLength(100),

                                TextInput::make('thema_code')
                                    ->label('Thema Code')
                                    ->placeholder('مثال: FB')
                                    ->maxLength(100),

                                TextInput::make('catalog_code')
                                    ->label('Catalog Code')
                                    ->placeholder('رمز الفهرس الداخلي...')
                                    ->maxLength(100),
                            ]),
                    ]),

                Section::make('صورة الغلاف والنبذة التعريفية')
                    ->description('رفع غلاف الكتاب وإدراج الملخص التعريفي ونبذة المؤلف')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->schema([
                        FileUpload::make('cover_path')
                            ->label('غلاف الكتاب')
                            ->image()
                            ->disk('public')
                            ->directory('covers')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->maxSize(5120)
                            ->helperText('ارفع صورة الغلاف (JPG/PNG/WebP حتى 5 ميجابايت) أو سيتم استخدام المسار التلقائي /covers/{isbn}.jpg')
                            ->columnSpanFull(),

                        Textarea::make('synopsis')
                            ->label('نبذة عن الكتاب (الملخص)')
                            ->placeholder('اكتب ملخصاً وافياً وشاملاً عن محتوى الكتاب، الفكرة العامة، وأهم محاوره...')
                            ->rows(5)
                            ->columnSpanFull(),

                        Textarea::make('author_bio')
                            ->label('نبذة عن المؤلف')
                            ->placeholder('نبذة تعريفية مختصرة عن المؤلف وسيرته...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('روابط الشركاء ومنصات البيع (Partner Links)')
                    ->description('إضافة روابط الشراء الخارجية (جرير، أمازون، نيل وفرات، أبجد وغيرها)')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsible()
                    ->schema([
                        Repeater::make('platformLinks')
                            ->relationship('platformLinks')
                            ->label('قائمة الروابط والمنصات')
                            ->schema([
                                Select::make('platform_name')
                                    ->label('اسم المنصة / المتجر')
                                    ->options([
                                        'جرير' => 'مكتبة جرير (Jarir Bookstore)',
                                        'أمازون' => 'أمازون (Amazon)',
                                        'نيل وفرات' => 'نيل وفرات (Neelwafurat)',
                                        'أبجد' => 'أبجد (Abjjad)',
                                        'جوجل بلاي' => 'Google Play Books',
                                        'كيندل' => 'Amazon Kindle',
                                        'مكتبة العبيكان' => 'مكتبة العبيكان (Obeikan)',
                                        'بوك ديبوزيتوري' => 'Book Depository',
                                        'سوق فن' => 'سوق فن (SouqFann)',
                                        'دار المشرق' => 'متجر دار المشرق المباشر',
                                        'أخرى' => 'منصة أخرى',
                                    ])
                                    ->searchable()
                                    ->createOptionUsing(fn ($value) => $value)
                                    ->required()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 4,
                                    ]),

                                TextInput::make('url')
                                    ->label('رابط المنتج (URL)')
                                    ->placeholder('https://www.jarir.com/sa-ar/...')
                                    ->url()
                                    ->required()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 8,
                                    ]),

                                Hidden::make('sort_order')
                                    ->default(0),
                            ])
                            ->columns(12)
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->addActionLabel('إضافة رابط شريك جديد')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                !empty($state['platform_name']) 
                                    ? ($state['platform_name'] . (!empty($state['url']) ? ' - ' . Str::limit($state['url'], 40) : '')) 
                                    : 'رابط جديد'
                            )
                            ->columnSpanFull(),
                    ]),

                Section::make('خيارات النشر والظهور')
                    ->description('تحديد ظهور الكتاب في المتجر ووضعه ككتاب مميز')
                    ->icon('heroicon-o-eye')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('منشور في الكتالوج العام')
                                    ->helperText('عند التفعيل، يظهر الكتاب فوراً لزوار المتجر وفي نتائج البحث.')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->label('كتاب مميز (Featured)')
                                    ->helperText('إظهار الكتاب في شريط الكتب المختارة على الصفحة الرئيسية.')
                                    ->default(false),
                            ]),

                        Textarea::make('search_vector')
                            ->label('متجه البحث والفهرسة (Search Vector)')
                            ->placeholder('كلمات وفهارس إضافية للبحث السريع...')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('يُستخدم للبحث الشامل MATCH...AGAINST في قاعدة البيانات.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_path')
                    ->label('الغلاف')
                    ->disk('public')
                    ->defaultImageUrl(fn (Book $record): string => $record->cover_url)
                    ->square(),

                TextColumn::make('title')
                    ->label('عنوان الكتاب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Book $record): ?string => $record->subtitle ? Str::limit($record->subtitle, 45) : null)
                    ->wrap(),

                TextColumn::make('isbn')
                    ->label('ردمك (ISBN)')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الردمك')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('author.name')
                    ->label('المؤلف')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('language')
                    ->label('اللغة')
                    ->badge()
                    ->colors([
                        'primary' => 'Arabic',
                        'info' => 'English',
                        'warning' => 'French',
                    ])
                    ->sortable(),

                TextColumn::make('platform_links_count')
                    ->counts('platformLinks')
                    ->label('روابط الشركاء')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                ToggleColumn::make('is_published')
                    ->label('منشور'),

                IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name')
                    ->preload(),

                SelectFilter::make('author_id')
                    ->label('المؤلف')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('language')
                    ->label('اللغة')
                    ->options([
                        'Arabic' => 'العربية',
                        'English' => 'الإنجليزية',
                        'French' => 'الفرنسية',
                    ]),

                TernaryFilter::make('is_published')
                    ->label('حالة النشر')
                    ->placeholder('الكل')
                    ->trueLabel('منشور فقط')
                    ->falseLabel('مسودة فقط'),

                TernaryFilter::make('is_featured')
                    ->label('كتب مميزة')
                    ->placeholder('الكل')
                    ->trueLabel('المميزة فقط')
                    ->falseLabel('غير المميزة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Eager load critical relationships to eliminate N+1 queries during admin catalog rendering.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['author', 'category']);
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
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
        ];
    }
}
