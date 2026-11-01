<?php

namespace Tests\Feature;

use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    public function test_admin_can_render_categories_index_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/categories');
        $response->assertSuccessful();
    }

    public function test_admin_can_render_category_create_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/categories/create');
        $response->assertSuccessful();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin);

        $testName = 'Test Category ' . uniqid();
        
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => $testName,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('name', $testName)->first();
        $this->assertNotNull($category);

        // Clean up
        $category->delete();
    }
}
