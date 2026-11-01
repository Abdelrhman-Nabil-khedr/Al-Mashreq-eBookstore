<?php

namespace Tests\Feature;

use App\Filament\Resources\PublisherResource\Pages\CreatePublisher;
use App\Models\Publisher;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PublisherResourceTest extends TestCase
{
    public function test_admin_can_render_publishers_index_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/publishers');
        $response->assertSuccessful();
    }

    public function test_admin_can_render_publisher_create_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/publishers/create');
        $response->assertSuccessful();
    }

    public function test_admin_can_create_publisher(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin);

        $testName = 'Test Publisher ' . uniqid();
        
        Livewire::test(CreatePublisher::class)
            ->fillForm([
                'name' => $testName,
                'url' => 'https://example.com',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $publisher = Publisher::where('name', $testName)->first();
        $this->assertNotNull($publisher);
        $this->assertEquals('https://example.com', $publisher->url);

        // Clean up
        $publisher->delete();
    }
}
