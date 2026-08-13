<?php

namespace Tests\Feature;

use App\Models\SessionVideo;
use App\Models\User;
use App\Support\Cms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Cms caches overrides in a static, which outlives the rolled-back
        // database between tests in the same process.
        Cms::flush();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function video(array $attributes = []): SessionVideo
    {
        return SessionVideo::create(array_merge([
            'title' => 'Week 1 — Getting started',
            'youtube_id' => 'dQw4w9WgXcQ',
            'is_published' => true,
            'sort_order' => 1,
        ], $attributes));
    }

    public function test_admin_can_add_a_video_from_a_watch_url(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/videos', [
                'title' => 'Week 3 — Automations',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s',
                'session_label' => 'Cohort 1 · Week 3',
                'recorded_on' => '2026-07-04',
                'is_published' => '1',
            ])
            ->assertRedirect(route('dashboard.videos'));

        $video = SessionVideo::firstOrFail();

        $this->assertSame('dQw4w9WgXcQ', $video->youtube_id);
        $this->assertSame('Cohort 1 · Week 3', $video->session_label);
        $this->assertTrue($video->is_published);
        $this->assertSame(1, $video->sort_order);
    }

    public function test_share_embed_and_shorts_links_are_all_normalised(): void
    {
        $this->assertSame('dQw4w9WgXcQ', SessionVideo::parseYoutubeId('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', SessionVideo::parseYoutubeId('https://www.youtube.com/embed/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', SessionVideo::parseYoutubeId('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', SessionVideo::parseYoutubeId('https://www.youtube.com/live/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', SessionVideo::parseYoutubeId('dQw4w9WgXcQ'));
        $this->assertNull(SessionVideo::parseYoutubeId('https://vimeo.com/12345'));
        $this->assertNull(SessionVideo::parseYoutubeId(''));
    }

    public function test_a_link_that_is_not_youtube_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/videos', [
                'title' => 'Not a video',
                'youtube_url' => 'https://vimeo.com/12345',
            ])
            ->assertSessionHasErrors('youtube_url');

        $this->assertSame(0, SessionVideo::count());
    }

    public function test_the_same_video_cannot_be_added_twice(): void
    {
        $this->video();

        $this->actingAs($this->admin())
            ->post('/dashboard/videos', [
                'title' => 'Duplicate',
                'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->assertSessionHasErrors('youtube_url');

        $this->assertSame(1, SessionVideo::count());
    }

    public function test_editing_a_video_keeps_its_own_link_valid(): void
    {
        $video = $this->video();

        $this->actingAs($this->admin())
            ->put('/dashboard/videos/'.$video->id, [
                'title' => 'Week 1 — Renamed',
                'youtube_url' => $video->watch_url,
            ])
            ->assertRedirect(route('dashboard.videos'));

        $video->refresh();

        $this->assertSame('Week 1 — Renamed', $video->title);
        // The checkbox is absent from the payload, so the video is hidden.
        $this->assertFalse($video->is_published);
    }

    public function test_admin_can_delete_a_video(): void
    {
        $video = $this->video();

        $this->actingAs($this->admin())
            ->delete('/dashboard/videos/'.$video->id)
            ->assertRedirect(route('dashboard.videos'));

        $this->assertSame(0, SessionVideo::count());
    }

    public function test_public_page_shows_published_videos_only(): void
    {
        $this->video(['title' => 'Published session']);
        $this->video([
            'title' => 'Hidden session',
            'youtube_id' => 'abcdefghijk',
            'is_published' => false,
            'sort_order' => 2,
        ]);

        $this->get('/videos')
            ->assertOk()
            ->assertSee('Published session')
            ->assertDontSee('Hidden session');
    }

    public function test_the_page_copy_comes_from_the_cms(): void
    {
        $this->video();

        $this->get('/videos')
            ->assertOk()
            ->assertSee('Missed a session? Watch it here.')
            ->assertSee('Join the next one →', false);

        $this->actingAs($this->admin())
            ->put('/dashboard/cms/videos', ['fields' => [
                'hero_title' => 'Catch up on any session',
                'cta_button' => 'Come to the next one',
            ]])
            ->assertRedirect(route('dashboard.cms.edit', 'videos'));

        $this->get('/videos')
            ->assertOk()
            ->assertSee('Catch up on any session')
            ->assertSee('Come to the next one')
            ->assertDontSee('Missed a session? Watch it here.');
    }

    public function test_the_empty_state_copy_comes_from_the_cms(): void
    {
        // No videos published: the page still has to say something useful.
        $this->get('/videos')
            ->assertOk()
            ->assertSee('Nothing here just yet');
    }

    public function test_guests_cannot_reach_the_dashboard_crud(): void
    {
        $this->get('/dashboard/videos')->assertRedirect('/login');
        $this->post('/dashboard/videos', ['title' => 'x', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ'])
            ->assertRedirect('/login');
        $this->assertSame(0, SessionVideo::count());
    }
}
