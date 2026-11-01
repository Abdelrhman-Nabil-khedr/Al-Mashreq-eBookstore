<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'الكتالوج والمحتوى';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'مقال';

    protected static ?string $pluralModelLabel = 'المدونة والمقالات';

    protected static ?string $navigationLabel = 'المدونة والمقالات';

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() || $user->isEditor());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات المقال الأساسية')
                    ->description('العنوان، الرابط المخصص، الكاتب، وصورة الغلاف الرئيسية')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان المقال')
                                    ->placeholder('اكتب عنوان المقال هنا...')
                                    ->required()
                                    ->maxLength(1000)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation === 'create' || $operation === 'edit') {
                                            $set('slug', Str::slug($state) ?: urlencode($state ?? ''));
                                        }
                                    }),

                                TextInput::make('slug')
                                    ->label('الرابط اللطيف (Slug)')
                                    ->placeholder('رابط المقال في المتصفح...')
                                    ->required()
                                    ->maxLength(1000)
                                    ->unique(BlogPost::class, 'slug', ignoreRecord: true)
                                    ->helperText('يُستخدم في مسار الرابط: /blog/article-slug'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('author_id')
                                    ->label('الكاتب / الناشر')
                                    ->relationship('author', 'name')
                                    ->default(fn () => auth()->id())
                                    ->searchable()
                                    ->preload()
                                    ->helperText('المستخدم المسؤول عن كتابة هذا المقال'),

                                FileUpload::make('cover_image')
                                    ->label('صورة غلاف المقال')
                                    ->image()
                                    ->disk('public')
                                    ->directory('blog-covers')
                                    ->visibility('public')
                                    ->imageResizeMode('cover')
                                    ->maxSize(5120)
                                    ->helperText('ارفع صورة مميزة للمقال (JPG, PNG, WebP حتى 5 ميجابايت)'),
                            ]),

                        Textarea::make('excerpt')
                            ->label('المقتطف / النبذة الموجزة')
                            ->placeholder('ملخص سريع للمقال يُعرض في بطاقات المدونة ومحركات البحث...')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->helperText('يظهر هذا الملخص في المعاينة وقوائم المقالات'),
                    ]),

                Section::make('محتوى المقال الكامل (المحرر المرئي)')
                    ->description('كتابة وتنسيق المقال باستخدام أدوات المحرر المرئي الغني')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        RichEditor::make('content')
                            ->label('نص المقال')
                            ->required()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('blog-attachments')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),
                    ]),

                Section::make('حالة وإعدادات النشر')
                    ->description('تحديد ظهور المقال في الموقع وتاريخ النشر')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('نشر المقال للزوار الآن')
                                    ->helperText('عند التفعيل سيصبح المقال متاحاً في واجهة المدونة العامة')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                        if ($state && empty($get('published_at'))) {
                                            $set('published_at', now()->toDateTimeString());
                                        }
                                    }),

                                DateTimePicker::make('published_at')
                                    ->label('تاريخ ووقت النشر')
                                    ->placeholder('اختر موعد النشر...')
                                    ->native(false)
                                    ->helperText('تاريخ ظهور المقال، أو موعد مجدول مستقبلاً'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('الغلاف')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('assets/logo.png')),

                TextColumn::make('title')
                    ->label('عنوان المقال')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(45)
                    ->tooltip(fn (BlogPost $record): string => $record->title),

                TextColumn::make('slug')
                    ->label('الرابط')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('author.name')
                    ->label('الكاتب')
                    ->sortable()
                    ->searchable()
                    ->placeholder('إدارة المنصة'),

                ToggleColumn::make('is_published')
                    ->label('منشور')
                    ->sortable()
                    ->afterStateUpdated(function (BlogPost $record, $state) {
                        if ($state && !$record->published_at) {
                            $record->update(['published_at' => now()]);
                        }
                    }),

                TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('حالة النشر')
                    ->placeholder('جميع المقالات')
                    ->trueLabel('المنشورة فقط')
                    ->falseLabel('المسودات فقط'),

                Filter::make('has_cover')
                    ->label('المقالات بصورة غلاف')
                    ->query(fn (Builder $query) => $query->whereNotNull('cover_image')->where('cover_image', '!=', '')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'view' => Pages\ViewBlogPost::route('/{record}'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
