<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Html;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorImageTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_an_admin_can_upload_an_editor_image_and_gets_an_absolute_url(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())
            ->post(route('editor.image'), ['image' => UploadedFile::fake()->image('banner.png', 800, 300)])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $url = $response->json('url');

        $this->assertStringStartsWith(rtrim(config('app.url'), '/').'/storage/editor/', $url);
        $this->assertSame(1, count(Storage::disk('public')->files('editor')));
    }

    public function test_non_images_and_oversized_files_are_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('editor.image'), ['image' => UploadedFile::fake()->create('payload.php', 10, 'text/php')])
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)
            ->post(route('editor.image'), ['image' => UploadedFile::fake()->image('huge.jpg')->size(4096)])
            ->assertSessionHasErrors('image');

        $this->assertSame([], Storage::disk('public')->files('editor'));
    }

    public function test_guests_cannot_upload(): void
    {
        Storage::fake('public');

        $this->post(route('editor.image'), ['image' => UploadedFile::fake()->image('a.png')])
            ->assertRedirect(route('login'));
    }

    public function test_the_sanitizer_keeps_uploaded_images(): void
    {
        $clean = Html::clean('<p>Hi</p><p><img src="https://applydacademy.com/storage/editor/a.png" alt="Banner"></p>');

        $this->assertStringContainsString('<img', $clean);
        $this->assertStringContainsString('storage/editor/a.png', $clean);
        $this->assertStringContainsString('alt="Banner"', $clean);
    }

    public function test_an_image_only_body_survives_the_empty_content_guard(): void
    {
        $clean = Html::clean('<p><img src="https://applydacademy.com/storage/editor/a.png"></p>');

        $this->assertNotNull($clean);
        $this->assertStringContainsString('<img', $clean);
    }

    public function test_base64_and_javascript_image_sources_are_stripped(): void
    {
        $base64 = Html::clean('<p>Hi</p><p><img src="data:image/png;base64,iVBORw0KGgo="></p>');
        $this->assertStringNotContainsString('data:', (string) $base64);

        $js = Html::clean('<p>Hi</p><p><img src="javascript:alert(1)"></p>');
        $this->assertStringNotContainsString('javascript:', (string) $js);
    }

    public function test_the_editor_exposes_an_image_button_to_signed_in_admins_only(): void
    {
        // @json() escapes the slashes, so match the encoded form.
        $this->actingAs($this->admin())
            ->get(route('dashboard.email-templates.edit', 'registration_confirmation'))
            ->assertOk()
            ->assertSee('editor\/image', false);
    }

    public function test_the_guest_editor_gets_no_image_button(): void
    {
        // The public company signup form uses the same partial but must not
        // advertise an upload endpoint guests cannot reach.
        $this->get(route('companies.register'))
            ->assertOk()
            ->assertSee('UPLOAD_URL = null', false)
            ->assertDontSee('editor\/image', false);
    }
}
