<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Filament\Panel;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    private function getSuperAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@almashreqebookstore.com'],
            [
                'name' => 'مدير مكتبة المشرق',
                'password' => Hash::make('Admin@Mashreq2026!'),
                'role' => User::ROLE_SUPER_ADMIN,
                'email_verified_at' => now(),
            ]
        );
    }

    private function getEditor(): User
    {
        return User::firstOrCreate(
            ['email' => 'editor@almashreqebookstore.com'],
            [
                'name' => 'محرر المحتوى',
                'password' => Hash::make('Editor@Mashreq2026!'),
                'role' => User::ROLE_EDITOR,
                'email_verified_at' => now(),
            ]
        );
    }

    private function getRegularUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'standard_user@example.com'],
            [
                'name' => 'مستخدم عادي',
                'password' => Hash::make('Password123!'),
                'role' => User::ROLE_USER,
                'email_verified_at' => now(),
            ]
        );
    }

    public function test_can_access_panel_role_permissions(): void
    {
        $superAdmin = $this->getSuperAdmin();
        $editor = $this->getEditor();
        $user = $this->getRegularUser();

        $panel = new Panel();

        $this->assertTrue($superAdmin->canAccessPanel($panel));
        $this->assertTrue($editor->canAccessPanel($panel));
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_super_admin_can_render_users_index_page(): void
    {
        $superAdmin = $this->getSuperAdmin();

        $response = $this->actingAs($superAdmin)->get('/admin/users');
        $response->assertSuccessful();
    }

    public function test_super_admin_can_render_user_create_page(): void
    {
        $superAdmin = $this->getSuperAdmin();

        $response = $this->actingAs($superAdmin)->get('/admin/users/create');
        $response->assertSuccessful();
    }

    public function test_super_admin_can_create_new_editor_user(): void
    {
        $superAdmin = $this->getSuperAdmin();
        $this->actingAs($superAdmin);

        $testEmail = 'new_editor_' . uniqid() . '@almashreq.com';

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'محرر تجريبي',
                'email' => $testEmail,
                'password' => 'SecurePass2026!',
                'role' => User::ROLE_EDITOR,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', $testEmail)->first();
        $this->assertNotNull($created);
        $this->assertEquals(User::ROLE_EDITOR, $created->role);
        $this->assertEquals('محرر تجريبي', $created->name);
        $this->assertTrue(Hash::check('SecurePass2026!', $created->password));
    }

    public function test_editor_cannot_access_user_resource(): void
    {
        $editor = $this->getEditor();

        $response = $this->actingAs($editor)->get('/admin/users');
        $response->assertForbidden();

        $createResponse = $this->actingAs($editor)->get('/admin/users/create');
        $createResponse->assertForbidden();
    }

    public function test_editor_can_access_books_authors_and_categories(): void
    {
        $editor = $this->getEditor();

        $this->actingAs($editor)->get('/admin/books')->assertSuccessful();
        $this->actingAs($editor)->get('/admin/authors')->assertSuccessful();
        $this->actingAs($editor)->get('/admin/categories')->assertSuccessful();
    }

    public function test_editor_cannot_access_restricted_resources(): void
    {
        $editor = $this->getEditor();

        // Publishers, Blog posts, Pages are restricted to super_admin
        $this->actingAs($editor)->get('/admin/publishers')->assertForbidden();
        $this->actingAs($editor)->get('/admin/blog-posts')->assertForbidden();
        $this->actingAs($editor)->get('/admin/pages')->assertForbidden();
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $superAdmin = $this->getSuperAdmin();
        $policy = new \App\Policies\UserPolicy();

        $this->assertFalse($policy->delete($superAdmin, $superAdmin));
    }
}
