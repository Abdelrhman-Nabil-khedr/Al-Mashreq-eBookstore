<?php

namespace Tests\Feature;

use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    public function test_admin_can_render_pages_index_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/pages');
        $response->assertSuccessful();
    }

    public function test_admin_can_render_page_edit_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $page = Page::first();
        if (!$page) {
            $this->markTestSkipped('No page found in database.');
        }

        $response = $this->actingAs($admin)->get(\App\Filament\Resources\PageResource::getUrl('edit', ['record' => $page]));
        $response->assertSuccessful();
    }

    public function test_public_pages_api_endpoint(): void
    {
        $response = $this->getJson('/api/v1/pages/about');
        $response->assertSuccessful()
                 ->assertJsonPath('data.slug', 'about');
        $this->assertStringContainsString('من نحن', $response->json('data.title'));

        $visionResponse = $this->getJson('/api/v1/pages/vision');
        $visionResponse->assertSuccessful()
                       ->assertJsonPath('data.slug', 'vision')
                       ->assertJsonPath('data.title', 'الرؤية والمهمة');
    }
}
