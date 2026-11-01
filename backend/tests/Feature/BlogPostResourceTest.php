<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Models\BlogPost;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class BlogPostResourceTest extends TestCase
{
    public function test_admin_can_render_blog_posts_index_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/blog-posts');
        $response->assertSuccessful();
    }

    public function test_admin_can_render_blog_post_create_page(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/blog-posts/create');
        $response->assertSuccessful();
    }

    public function test_admin_can_create_blog_post(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin'])->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin);

        $testTitle = 'Test Post ' . uniqid();
        $testSlug = 'test-post-' . uniqid();

        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'title' => $testTitle,
                'slug' => $testSlug,
                'excerpt' => 'This is a test post excerpt.',
                'content' => '<h2>Test Header</h2><p>This is test content.</p>',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::where('slug', $testSlug)->first();
        $this->assertNotNull($post);
        $this->assertEquals($testTitle, $post->title);
        $this->assertTrue($post->is_published);

        // Clean up
        $post->delete();
    }

    public function test_public_blog_api_endpoints(): void
    {
        $response = $this->getJson('/api/v1/blog');
        $response->assertSuccessful()
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'meta' => ['total', 'current_page', 'per_page'],
                 ]);
    }
}
