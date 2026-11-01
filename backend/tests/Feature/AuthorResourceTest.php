<?php

namespace Tests\Feature;

use App\Filament\Resources\AuthorResource\Pages\CreateAuthor;
use App\Models\Author;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorResourceTest extends TestCase
{
    public function test_admin_can_render_authors_index_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/authors');
        $response->assertSuccessful();
    }

    public function test_admin_can_render_author_create_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/authors/create');
        $response->assertSuccessful();
    }

    public function test_admin_can_create_author(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin);

        $testName = 'Test Author ' . uniqid();
        
        Livewire::test(CreateAuthor::class)
            ->fillForm([
                'name' => $testName,
                'biography' => '<p>This is a test biography.</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $author = Author::where('name', $testName)->first();
        $this->assertNotNull($author);
        $this->assertEquals('<p>This is a test biography.</p>', $author->biography);

        // Clean up
        $author->delete();
    }
}
