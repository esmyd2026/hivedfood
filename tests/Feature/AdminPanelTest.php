<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\ContentSection;
use App\Models\Faq;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\SiteSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@hivedfood.test',
            'password' => Hash::make('secret-password'),
            'is_admin' => true,
        ]);
    }

    private function member(): User
    {
        return User::query()->create([
            'name' => 'Usuario',
            'email' => 'usuario@hivedfood.test',
            'password' => Hash::make('secret-password'),
            'is_admin' => false,
        ]);
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->getJson('/admin/api/banners')->assertUnauthorized();
    }

    public function test_a_non_admin_account_cannot_reach_the_panel(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get('/admin')->assertRedirect(route('admin.login'));
        $this->actingAs($member)->getJson('/admin/api/banners')->assertForbidden();
    }

    public function test_an_administrator_can_log_in(): void
    {
        $this->admin();

        $response = $this->post(route('admin.login.attempt'), [
            'email' => 'admin@hivedfood.test',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.panel'));
        $this->assertAuthenticated();
    }

    public function test_a_non_admin_cannot_log_in_to_the_panel(): void
    {
        $this->member();

        $this->post(route('admin.login.attempt'), [
            'email' => 'usuario@hivedfood.test',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        $this->admin();

        $this->post(route('admin.login.attempt'), [
            'email' => 'admin@hivedfood.test',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_banners_can_be_created_with_an_uploaded_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/api/banners', [
            'title' => 'Nuevo banner',
            'description' => 'Texto del banner',
            'cta_text' => 'Ver más',
            'cta_url' => '#plataforma',
            'theme' => 'dark',
            'align' => 'left',
            'transition' => 'fade',
            'duration' => 6000,
            'is_active' => true,
            'image_desktop' => UploadedFile::fake()->image('hero.jpg', 1200, 960),
        ]);

        $response->assertCreated();

        $banner = Banner::query()->firstOrFail();

        $this->assertSame('Nuevo banner', $banner->title);
        Storage::disk('public')->assertExists($banner->image_desktop);
    }

    public function test_banner_validation_rejects_an_end_date_before_the_start(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/api/banners', [
                'title' => 'Banner',
                'theme' => 'dark',
                'align' => 'left',
                'transition' => 'fade',
                'starts_at' => '2026-05-10 10:00:00',
                'ends_at' => '2026-05-01 10:00:00',
            ])
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_banners_can_be_reordered(): void
    {
        $first = Banner::query()->create(['title' => 'Primero', 'sort_order' => 10]);
        $second = Banner::query()->create(['title' => 'Segundo', 'sort_order' => 20]);

        $this->actingAs($this->admin())
            ->postJson('/admin/api/banners/reorder', ['order' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame(10, $second->fresh()->sort_order);
        $this->assertSame(20, $first->fresh()->sort_order);
    }

    public function test_a_banner_can_be_toggled(): void
    {
        $banner = Banner::query()->create(['title' => 'Banner', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->postJson("/admin/api/banners/{$banner->id}/toggle", ['field' => 'is_active', 'value' => false])
            ->assertOk();

        $this->assertFalse($banner->fresh()->is_active);
    }

    public function test_only_whitelisted_columns_can_be_toggled(): void
    {
        $banner = Banner::query()->create(['title' => 'Banner']);

        $this->actingAs($this->admin())
            ->postJson("/admin/api/banners/{$banner->id}/toggle", ['field' => 'title', 'value' => true])
            ->assertUnprocessable();
    }

    public function test_structural_sections_cannot_be_deleted(): void
    {
        $section = ContentSection::query()->create([
            'key' => 'hero',
            'name' => 'Hero',
            'component' => 'hero',
            'is_locked' => true,
        ]);

        $this->actingAs($this->admin())
            ->deleteJson("/admin/api/secciones/{$section->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('content_sections', ['id' => $section->id]);
    }

    public function test_a_regular_section_can_be_deleted(): void
    {
        $section = ContentSection::query()->create([
            'key' => 'extra',
            'name' => 'Extra',
            'component' => 'about',
        ]);

        $this->actingAs($this->admin())
            ->deleteJson("/admin/api/secciones/{$section->id}")
            ->assertOk();

        $this->assertDatabaseMissing('content_sections', ['id' => $section->id]);
    }

    public function test_section_json_settings_round_trip(): void
    {
        $section = ContentSection::query()->create([
            'key' => 'extra',
            'name' => 'Extra',
            'component' => 'about',
        ]);

        $this->actingAs($this->admin())
            ->postJson("/admin/api/secciones/{$section->id}", [
                'key' => 'extra',
                'name' => 'Extra',
                'component' => 'about',
                'settings' => ['background' => 'charcoal', 'columns' => 3],
                'buttons' => [['label' => 'Ver', 'url' => '#ver', 'style' => 'primary']],
            ])
            ->assertOk();

        $section->refresh();

        $this->assertSame('charcoal', $section->settings['background']);
        $this->assertSame('Ver', $section->buttons[0]['label']);
    }

    public function test_settings_are_saved_and_the_cache_is_refreshed(): void
    {
        $this->seed(SiteSettingsSeeder::class);

        $this->actingAs($this->admin())
            ->postJson('/admin/api/configuracion', [
                'values' => [
                    'whatsapp_number' => '51999111222',
                    'pricing_published' => true,
                ],
            ])
            ->assertOk();

        $this->assertSame('51999111222', SiteSetting::get('whatsapp_number'));
        $this->assertTrue(SiteSetting::get('pricing_published'));
    }

    public function test_unknown_setting_keys_are_ignored(): void
    {
        $this->seed(SiteSettingsSeeder::class);

        $this->actingAs($this->admin())
            ->postJson('/admin/api/configuracion', ['values' => ['clave_inventada' => 'x']])
            ->assertOk();

        $this->assertDatabaseMissing('site_settings', ['key' => 'clave_inventada']);
    }

    public function test_the_dashboard_reports_configuration_gaps(): void
    {
        $this->seed(SiteSettingsSeeder::class);

        $response = $this->actingAs($this->admin())->getJson('/admin/api/dashboard');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['summary', 'checklist', 'pricing', 'recent_leads', 'recent_quotations']]);

        $keys = collect($response->json('data.checklist'))->pluck('key');

        // WhatsApp y el correo de avisos se entregan sin configurar.
        $this->assertTrue($keys->contains('whatsapp'));
        $this->assertTrue($keys->contains('notification_email'));

        // Los precios sí vienen publicados: ese aviso no debe aparecer.
        $this->assertFalse($keys->contains('pricing'));
    }

    public function test_faqs_are_managed_through_the_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/admin/api/faqs', ['question' => '¿Funciona en móvil?', 'answer' => 'Sí.'])
            ->assertCreated();

        $faq = Faq::query()->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/admin/api/faqs/{$faq->id}", ['question' => '¿Funciona en móvil?', 'answer' => 'Sí, mobile-first.'])
            ->assertOk();

        $this->assertSame('Sí, mobile-first.', $faq->fresh()->answer);

        $this->actingAs($admin)->deleteJson("/admin/api/faqs/{$faq->id}")->assertOk();
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_image_paths_are_exposed_as_urls_for_the_panel(): void
    {
        Banner::query()->create(['title' => 'Banner', 'image_desktop' => 'banners/demo.png']);

        $response = $this->actingAs($this->admin())->getJson('/admin/api/banners');

        $response->assertOk();
        $this->assertStringContainsString('banners/demo.png', $response->json('data.0.image_desktop_url'));
    }

    public function test_an_administrator_can_log_out(): void
    {
        $this->actingAs($this->admin())->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
