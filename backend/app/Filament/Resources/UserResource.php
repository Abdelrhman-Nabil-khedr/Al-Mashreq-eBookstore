<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'إعدادات النظام';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمون';

    protected static ?string $navigationLabel = 'إدارة المستخدمين';

    /* =========================================================================
     | Authorization / Access Control (Task 3)
     | ========================================================================= */

    /**
     * ONLY 'super_admin' users can see and access the UserResource.
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && $user->isSuperAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        return $user instanceof User && $user->isSuperAdmin() && $user->id !== $record->id;
    }

    /* =========================================================================
     | Form Schema (Task 2)
     | ========================================================================= */

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الحساب والصلاحية')
                    ->description('إدارة بيانات المشرفين والمحررين وصلاحيات الوصول إلى لوحة الإدارة.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'اتركه فارغاً إذا كنت لا ترغب في تغيير كلمة المرور الحالية.'
                                : 'أدخل كلمة مرور قوية للحساب الجديد (8 أحرف على الأقل).'
                            ),

                        Forms\Components\Select::make('role')
                            ->label('الدور والصلاحية')
                            ->options([
                                'super_admin' => 'مدير عام',
                                'editor' => 'محرر بيانات',
                            ])
                            ->required()
                            ->default('editor')
                            ->native(false)
                            ->helperText('ملاحظة: لا يمكن اختيار "مستخدم عادي" من لوحة الإدارة، فقط حسابات الفريق الإداري.'),
                    ])
                    ->columns(2),
            ]);
    }

    /* =========================================================================
     | Table Schema (Task 2)
     | ========================================================================= */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin', 'admin' => 'مدير عام',
                        'editor' => 'محرر بيانات',
                        'user', 'customer' => 'مستخدم عادي',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin', 'admin' => 'danger',
                        'editor' => 'warning',
                        'user', 'customer' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('تصفية حسب الدور')
                    ->options([
                        'super_admin' => 'مدير عام',
                        'editor' => 'محرر بيانات',
                        'user' => 'مستخدم عادي',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool => auth()->id() !== $record->id),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            $currentUserId = auth()->id();
                            if ($records->contains('id', $currentUserId)) {
                                Notification::make()
                                    ->warning()
                                    ->title('تحذير')
                                    ->body('تم استبعاد حسابك الشخصي من قائمة الحذف لحمايته من الحذف الذاتي.')
                                    ->send();

                                $action->records($records->reject(fn ($record) => $record->id === $currentUserId));
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
