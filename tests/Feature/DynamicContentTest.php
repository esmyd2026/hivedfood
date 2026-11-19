<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteContentService;
use Database\Seeders\BannerSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\SeoSeeder;
use Database\Seeders\SiteSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Every visible label, number and colour must come from the database, so the
 * site can be reshaped from the panel without touching code.
 */
class DynamicContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SiteSettingsSeeder::class, SeoSeeder::class, BannerSeeder::class, ContentSeeder::class]);
    }

    private function admin(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'admin@hivedfood.test'],
            ['name' => 'Admin', 'password' => Hash::make('secret-password'), 'is_admin' => true]
        );
    }

    public function test_interface_texts_travel_in_the_public_payload(): void
    {
        $texts = $this->getJson('/api/content')->json('data.settings.texts');

        $this->assertSame('Plataforma en producción', $texts['hero_badge_subtitle']);
        $this->assertSame('Hacer pedido', $texts['preview_cta_label']);
        $this->assertSame('¿Qué tipo de negocio tienes?', $texts['quoter_business_title']);
        $this->assertSame('Ver tienda', $texts['demo_link_label']);
        $this->assertSame('Sesión de 30 minutos', $texts['cta_perks'][0]['label']);
    }

    public function test_an_edited_text_reaches_the_site_immediately(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/api/configuracion', [
                'values' => [
                    'whatsapp_float_label' => 'Escríbenos ahora',
                    'quoter_business_title' => '¿Cuál es tu rubro?',
                ],
            ])
            ->assertOk();

        $texts = $this->getJson('/api/content')->json('data.settings.texts');

        $this->assertSame('Escríbenos ahora', $texts['whatsapp_float_label']);
        $this->assertSame('¿Cuál es tu rubro?', $texts['quoter_business_title']);
    }

    public function test_a_blank_text_is_omitted_so_the_component_default_applies(): void
    {
        SiteSetting::query()->where('key', 'demo_link_label')->update(['value' => '']);
        SiteSetting::flush();

        $texts = $this->getJson('/api/content')->json('data.settings.texts');

        $this->assertArrayNotHasKey('demo_link_label', $texts);
    }

    public function test_the_preview_palettes_are_administrable(): void
    {
        $palettes = $this->getJson('/api/content')->json('data.settings.preview_palettes');

        $this->assertCount(4, $palettes);
        $this->assertSame('Miel', $palettes[0]['name']);
        $this->assertSame('#F4B83F', $palettes[0]['accent']);
    }

    public function test_the_brand_palette_is_injected_as_css_variables(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('--color-honey: #F4B83F;', false);
        $response->assertSee('--color-charcoal: #202A24;', false);
    }

    public function test_changing_a_colour_changes_the_rendered_document(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/api/configuracion', ['values' => ['color_honey' => '#3FA66B']])
            ->assertOk();

        $this->get('/')->assertSee('--color-honey: #3FA66B;', false);
    }

    public function test_an_invalid_colour_never_reaches_the_stylesheet(): void
    {
        SiteSetting::query()->where('key', 'color_honey')->update([
            'value' => 'red; } body { display: none } :root {',
        ]);
        SiteSetting::flush();

        $variables = app(SiteContentService::class)->cssVariables();

        $this->assertArrayNotHasKey('--color-honey', $variables);

        // The style block only ever carries validated hex colours.
        $html = $this->get('/')->getContent();
        preg_match_all('/<style>(.*?)<\/style>/s', $html, $matches);

        foreach ($matches[1] as $css) {
            $this->assertStringNotContainsString('display: none', $css);
        }
    }

    public function test_a_setting_cannot_break_out_of_the_payload_script(): void
    {
        SiteSetting::query()->where('key', 'footer_text')->update([
            'value' => '</script><script>window.hacked = 1</script>',
        ]);
        SiteSetting::flush();

        $html = $this->get('/')->getContent();

        // Neither the injected payload nor the server-rendered fallback may
        // emit an executable tag: both escape the value.
        $this->assertStringNotContainsString('<script>window.hacked', $html);

        // Inside the JSON payload the tag travels hex-escaped.
        preg_match('/window\.__HIVEDFOOD__ = (.*);/', $html, $matches);

        $this->assertNotEmpty($matches, 'El payload no se encontro en el documento.');
        $this->assertStringNotContainsString('</script>', $matches[1]);
        $this->assertStringContainsString('\u003C', $matches[1]);
    }

    public function test_the_admin_panel_also_follows_the_configured_palette(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('--color-honey: #F4B83F;', false);
    }

    public function test_commercial_prices_never_leak_into_the_public_settings(): void
    {
        $settings = $this->getJson('/api/content')->json('data.settings');

        $this->assertArrayNotHasKey('pricing_published', $settings['texts']);
        $this->assertArrayNotHasKey('quotation_conditions', $settings);
        $this->assertArrayNotHasKey('notification_email', $settings);
    }
}
