<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'الكتالوج والمحتوى';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'صفحة ثابتة';

    protected static ?string $pluralModelLabel = 'الصفحات الثابتة';

    protected static ?string $navigationLabel = 'إدارة الصفحات الثابتة';

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() ?? false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('PageDetails')
                    ->tabs([
                        Tabs\Tab::make('المحتوى العربي')
                            ->icon('heroicon-o-language')
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان الصفحة بالعربية')
                                    ->placeholder('مثال: من نحن، الرؤية والمهمة...')
                                    ->required()
                                    ->maxLength(500)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set, ?Page $record) {
                                        if ($operation === 'create' && empty($record?->slug)) {
                                            $set('slug', Str::slug($state) ?: urlencode($state ?? ''));
                                        }
                                    }),

                                TextInput::make('subtitle')
                                    ->label('العنوان الفرعي / الوصف التمهيدي')
                                    ->placeholder('المقدمة أو السطر التوضيحي أعلى الصفحة...')
                                    ->maxLength(1000)
                                    ->columnSpanFull(),

                                RichEditor::make('content')
                                    ->label('محتوى الصفحة الكامل (المحرر المرئي RichEditor)')
                                    ->placeholder('اكتب ونسق محتوى الصفحة هنا بالكامل...')
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
                                    ->fileAttachmentsDirectory('page-attachments')
                                    ->fileAttachmentsVisibility('public')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('المحتوى الإنجليزي (English)')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                TextInput::make('title_en')
                                    ->label('Page Title (English)')
                                    ->placeholder('e.g. About Us, Vision & Mission...')
                                    ->maxLength(500),

                                TextInput::make('subtitle_en')
                                    ->label('Page Subtitle / Lead Paragraph')
                                    ->placeholder('Lead introduction text...')
                                    ->maxLength(1000)
                                    ->columnSpanFull(),

                                RichEditor::make('content_en')
                                    ->label('English Rich Content (RichEditor)')
                                    ->placeholder('Compose and style English page content...')
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
                                    ->fileAttachmentsDirectory('page-attachments')
                                    ->fileAttachmentsVisibility('public')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('إعدادات الرابط والسيو')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('slug')
                                            ->label('معرف الصفحة (Slug)')
                                            ->placeholder('مثال: about أو vision')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(Page::class, 'slug', ignoreRecord: true)
                                            ->helperText('المعرف الثابت في واجهة برمجة التطبيقات (API) وعناوين الصفحات')
                                            ->disabled(fn (?Page $record) => in_array($record?->slug, ['about', 'vision'])),

                                        Toggle::make('is_published')
                                            ->label('حالة النشر (مفعلة للزوار)')
                                            ->default(true)
                                            ->helperText('إتاحة الصفحة للاسترجاع عبر الـ API والواجهة الأمامية'),
                                    ]),

                                TextInput::make('meta_title')
                                    ->label('عنوان السيو (Meta Title)')
                                    ->placeholder('العنوان لمحركات البحث...')
                                    ->maxLength(500)
                                    ->columnSpanFull(),

                                Textarea::make('meta_description')
                                    ->label('وصف السيو (Meta Description)')
                                    ->placeholder('الوصف التعريفي لمحركات البحث...')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('اسم الصفحة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('المعرف (Slug)')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'about' => 'warning',
                        'vision' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title_en')
                    ->label('Title (EN)')
                    ->toggleable()
                    ->placeholder('—'),

                ToggleColumn::make('is_published')
                    ->label('مفعلة')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'asc')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('حالة النشر')
                    ->placeholder('جميع الصفحات')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('المعطلة فقط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('slug', ['about', 'vision']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
