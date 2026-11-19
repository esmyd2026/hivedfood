<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    /** Idempotent, so a test may call it more than once. */
    private function admin(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'admin@hivedfood.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('secret-password'),
                'is_admin' => true,
            ]
        );
    }

    public function test_the_catalogue_registers_the_images_shipped_with_the_project(): void
    {
        $result = app(MediaLibrary::class)->sync();

        $this->assertGreaterThan(0, $result['registered']);

        // The brand assets live in /public and are read-only from the panel.
        $logo = Media::query()->where('path', '/images/brand/hivedfood-logo.svg')->first();

        $this->assertNotNull($logo);
        $this->assertSame('marca', $logo->folder);
        $this->assertSame('shipped', $logo->source);
        $this->assertTrue($logo->is_protected);
        $this->assertSame(260, $logo->width);
        $this->assertSame(52, $logo->height);
    }

    public function test_syncing_twice_does_not_duplicate_entries(): void
    {
        $library = app(MediaLibrary::class);
        $first = $library->sync();
        $second = $library->sync();

        $this->assertSame(0, $second['registered']);
        $this->assertSame($first['total'], $second['total']);
    }

    public function test_the_listing_is_guarded_and_folders_are_exposed(): void
    {
        $this->getJson('/admin/api/medios')->assertUnauthorized();

        $response = $this->actingAs($this->admin())->getJson('/admin/api/medios');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total'], 'folders']);
        $this->assertArrayHasKey('marca', $response->json('folders'));
    }

    public function test_images_can_be_uploaded_into_a_folder(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/api/medios', [
            'folder' => 'banners',
            'alt' => 'Portada de prueba',
            'files' => [
                UploadedFile::fake()->image('portada.jpg', 1200, 960),
                UploadedFile::fake()->image('segunda.png', 600, 400),
            ],
        ]);

        $response->assertCreated();
        $this->assertSame('2 imágenes subidas.', $response->json('message'));

        $media = Media::query()->get();

        $this->assertCount(2, $media);
        $this->assertTrue($media->every(fn (Media $item) => $item->folder === 'banners'));
        $this->assertTrue($media->every(fn (Media $item) => $item->alt === 'Portada de prueba'));
        $media->each(fn (Media $item) => Storage::disk('public')->assertExists($item->path));
    }

    public function test_non_images_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->postJson('/admin/api/medios', ['files' => [UploadedFile::fake()->create('malicioso.php', 10)]])
            ->assertJsonValidationErrors('files.0');
    }

    public function test_the_metadata_of_an_image_can_be_edited(): void
    {
        $media = Media::query()->create([
            'name' => 'foto.png',
            'path' => 'biblioteca/foto.png',
            'folder' => 'biblioteca',
        ]);

        $this->actingAs($this->admin())
            ->postJson("/admin/api/medios/{$media->id}", [
                'name' => 'Foto del local',
                'alt' => 'Fachada del restaurante',
                'folder' => 'demos',
            ])
            ->assertOk();

        $media->refresh();

        $this->assertSame('Foto del local', $media->name);
        $this->assertSame('Fachada del restaurante', $media->alt);
        $this->assertSame('demos', $media->folder);
    }

    public function test_usage_reports_every_place_an_image_is_referenced(): void
    {
        $banner = Banner::query()->create([
            'title' => 'Banner principal',
            'image_desktop' => '/images/hero/hero-storefront.svg',
        ]);

        SiteSetting::query()->create([
            'key' => 'logo',
            'type' => 'image',
            'group' => 'general',
            'value' => '/images/hero/hero-storefront.svg',
        ]);
        SiteSetting::flush();

        $usage = app(MediaLibrary::class)->usage('/images/hero/hero-storefront.svg');

        $this->assertCount(2, $usage);
        $this->assertSame('Banner · escritorio', $usage[0]['type']);
        $this->assertSame('Banner principal', $usage[0]['label']);
        $this->assertSame('Configuración general', $usage[1]['type']);
        $this->assertSame($banner->id, $usage[0]['id']);
    }

    public function test_replacing_a_file_repoints_every_reference(): void
    {
        Storage::fake('public');

        app(MediaLibrary::class)->sync();

        $media = Media::query()->where('path', '/images/hero/hero-storefront.svg')->firstOrFail();

        $banner = Banner::query()->create([
            'title' => 'Banner principal',
            'image_desktop' => $media->path,
            'image_mobile' => $media->path,
        ]);

        SiteSetting::query()->create([
            'key' => 'og_image',
            'type' => 'image',
            'group' => 'general',
            'value' => $media->path,
        ]);
        SiteSetting::flush();

        $response = $this->actingAs($this->admin())->post("/admin/api/medios/{$media->id}/reemplazar", [
            'file' => UploadedFile::fake()->image('nuevo-hero.jpg', 1200, 960),
        ]);

        $response->assertOk();

        $newPath = $response->json('data.path');
        $banner->refresh();

        $this->assertNotSame($media->path, $newPath);
        $this->assertSame($newPath, $banner->image_desktop);
        $this->assertSame($newPath, $banner->image_mobile);
        $this->assertSame($newPath, SiteSetting::get('og_image'));
    }

    public function test_project_assets_cannot_be_deleted(): void
    {
        app(MediaLibrary::class)->sync();

        $media = Media::query()->where('is_protected', true)->firstOrFail();

        $this->actingAs($this->admin())
            ->deleteJson("/admin/api/medios/{$media->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_deleting_an_image_in_use_requires_confirmation(): void
    {
        Storage::fake('public');

        $media = app(MediaLibrary::class)->store(UploadedFile::fake()->image('en-uso.jpg'), 'banners');

        Banner::query()->create(['title' => 'Banner', 'image_desktop' => $media->path]);

        $this->actingAs($this->admin())
            ->deleteJson("/admin/api/medios/{$media->id}")
            ->assertStatus(409)
            ->assertJsonStructure(['message', 'usage']);

        $this->assertDatabaseHas('media', ['id' => $media->id]);

        $this->actingAs($this->admin())
            ->deleteJson("/admin/api/medios/{$media->id}?force=1")
            ->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_an_unused_upload_can_be_deleted(): void
    {
        Storage::fake('public');

        $media = app(MediaLibrary::class)->store(UploadedFile::fake()->image('libre.jpg'), 'biblioteca');

        $this->actingAs($this->admin())
            ->deleteJson("/admin/api/medios/{$media->id}")
            ->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_an_image_from_the_library_can_be_reused_without_uploading_again(): void
    {
        app(MediaLibrary::class)->sync();

        $media = Media::query()->where('path', '/images/demos/demo-torki.svg')->firstOrFail();

        $this->actingAs($this->admin())
            ->postJson('/admin/api/banners', [
                'title' => 'Banner reutilizando imagen',
                'theme' => 'dark',
                'align' => 'left',
                'transition' => 'fade',
                'image_desktop_path' => $media->path,
            ])
            ->assertCreated();

        $this->assertSame($media->path, Banner::query()->value('image_desktop'));
    }

    public function test_a_path_outside_the_library_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/api/banners', [
                'title' => 'Banner',
                'theme' => 'dark',
                'align' => 'left',
                'transition' => 'fade',
                'image_desktop_path' => '../../etc/passwd',
            ])
            ->assertJsonValidationErrors('image_desktop_path');
    }

    public function test_uploads_from_a_resource_form_join_the_library(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post('/admin/api/banners', [
            'title' => 'Banner con subida directa',
            'theme' => 'dark',
            'align' => 'left',
            'transition' => 'fade',
            'image_desktop' => UploadedFile::fake()->image('directa.jpg', 1200, 960),
        ])->assertCreated();

        $path = Banner::query()->value('image_desktop');

        $this->assertDatabaseHas('media', ['path' => $path, 'folder' => 'banners']);
    }

    public function test_the_catalogue_drops_entries_whose_file_disappeared(): void
    {
        Media::query()->create([
            'name' => 'fantasma.png',
            'path' => 'biblioteca/fantasma.png',
            'folder' => 'biblioteca',
        ]);

        $result = app(MediaLibrary::class)->sync();

        $this->assertSame(1, $result['removed']);
        $this->assertDatabaseMissing('media', ['path' => 'biblioteca/fantasma.png']);
    }
}
