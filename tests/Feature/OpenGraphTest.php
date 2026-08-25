<?php
namespace Tests\Feature;
use App\Models\BlogCategory;
use App\Models\Company;
use App\Models\Course;
use App\Models\JobOpening;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenGraphTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_share_card_uses_its_cover_image(): void
    {
        $post = Post::create([
            'blog_category_id' => BlogCategory::create(['name' => 'Guides', 'slug' => 'guides'])->id,
            'title' => 'Five "quick" wins & more',
            'excerpt' => 'A short teaser for the card.',
            'body' => '<p>Body copy.</p>',
            'cover_image' => 'posts/cover.jpg',
            'author' => 'Ama',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $res = $this->get(route('blog.show', $post));
        $res->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString('property="og:image" content="'.asset('storage/posts/cover.jpg').'"', $html);
        $this->assertStringContainsString('name="twitter:image" content="'.asset('storage/posts/cover.jpg').'"', $html);
        $this->assertStringContainsString('property="og:type" content="article"', $html);
        $this->assertStringContainsString('content="A short teaser for the card."', $html);
        $this->assertStringContainsString('property="article:author" content="Ama"', $html);
        $this->assertStringContainsString('property="og:url" content="'.route('blog.show', $post).'"', $html);
        // Title is escaped, not raw.
        $this->assertStringContainsString('og:title" content="Five &quot;quick&quot; wins &amp; more"', $html);
        $this->assertStringNotContainsString('og-default.jpg', $html);
    }

    public function test_post_without_a_cover_falls_back_to_the_site_card(): void
    {
        $post = Post::create([
            'title' => 'No cover here',
            'body' => 'Body.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        // @section('og_image', null) would make Blade open a block section and
        // leak an output buffer, so the null path is pinned as well as the tag.
        $buffers = ob_get_level();

        $res = $this->get(route('blog.show', $post));
        $res->assertOk();

        $this->assertSame($buffers, ob_get_level(), 'Rendering leaked an output buffer.');
        $this->assertStringContainsString(
            'property="og:image" content="'.asset('img/og-default.jpg').'"',
            $res->getContent()
        );
    }

    public function test_course_share_card_uses_the_course_image(): void
    {
        $course = Course::create([
            'title' => 'Digital Marketing',
            'description' => '<p>Learn the whole funnel.</p>',
            'image' => 'courses/dm.jpg',
            'price' => 500,
        ]);

        $buffers = ob_get_level();
        $html = $this->get(route('courses.show', $course))->assertOk()->getContent();

        $this->assertSame($buffers, ob_get_level(), 'Rendering leaked an output buffer.');
        $this->assertStringContainsString('property="og:image" content="'.asset('storage/courses/dm.jpg').'"', $html);
        $this->assertStringContainsString('property="og:title" content="Digital Marketing"', $html);
        $this->assertStringContainsString('content="Learn the whole funnel."', $html);
    }

    public function test_course_without_an_image_does_not_leak_a_buffer(): void
    {
        $course = Course::create(['title' => 'No art', 'description' => 'Text.', 'price' => 0]);

        $buffers = ob_get_level();
        $html = $this->get(route('courses.show', $course))->assertOk()->getContent();

        $this->assertSame($buffers, ob_get_level(), 'Rendering leaked an output buffer.');
        $this->assertStringContainsString('property="og:image" content="'.asset('img/og-default.jpg').'"', $html);
    }

    public function test_job_without_a_company_logo_does_not_leak_a_buffer(): void
    {
        $company = Company::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Acme Ltd',
        ]);

        $opening = JobOpening::create([
            'company_id' => $company->id,
            'title' => 'Growth Lead',
            'description' => 'Own the funnel.',
            'sector' => JobOpening::SECTORS[0],
            'is_open' => true,
        ]);

        $buffers = ob_get_level();
        $html = $this->get(route('jobs.show', $opening))->assertOk()->getContent();

        $this->assertSame($buffers, ob_get_level(), 'Rendering leaked an output buffer.');
        $this->assertStringContainsString('property="og:image" content="'.asset('img/og-default.jpg').'"', $html);
        $this->assertStringContainsString('property="og:title" content="Growth Lead at Acme Ltd"', $html);
    }

    public function test_landing_page_still_gets_the_default_card(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('property="og:image" content="'.asset('img/og-default.jpg').'"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false);
    }
}
