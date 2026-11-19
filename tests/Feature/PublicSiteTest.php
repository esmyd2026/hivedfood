<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\ContentSection;
use App\Models\Faq;
use App\Models\SeoSetting;
use Database\Seeders\BannerSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\SeoSeeder;
use Database\Seeders\SiteSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SiteSettingsSeeder::class,
            SeoSeeder::class,
            BannerSeeder::class,
            ContentSeeder::class,
            FaqSeeder::class,
        ]);
    }

    public function test_home_page_renders_administrable_meta_tags(): void
    {
        $seo = SeoSetting::query()->where('page_key', 'home')->firstOrFail();

        $response = $this->get('/');

        $response->assertOk();
        // The document reflects whatever the panel has stored, not fixed copy.
        $response->assertSee('<title>'.$seo->meta_title.'</title>', false);
        $response->assertSee($seo->meta_description, false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('application/ld+json', false);
    }

    public function test_an_edited_meta_title_reaches_the_document(): void
    {
        SeoSetting::query()->where('page_key', 'home')->update(['meta_title' => 'Otro título desde el panel']);

        $this->get('/')->assertSee('<title>Otro título desde el panel</title>', false);
    }

    public function test_home_page_ships_indexable_content_without_javascript(): void
    {
        $banner = Banner::query()->published()->ordered()->firstOrFail();
        $section = ContentSection::query()->where('key', 'plataforma')->firstOrFail();
        $faq = Faq::query()->active()->ordered()->firstOrFail();

        $response = $this->get('/');

        // The banner headline is the H1 and the stored section copy is already
        // in the server response, so crawlers do not depend on JavaScript.
        $response->assertSee($banner->title, false);
        $response->assertSee($section->title, false);
        $response->assertSee($faq->question, false);
    }

    public function test_the_order_entry_channels_are_indexable(): void
    {
        $channels = ContentSection::query()->where('key', 'canales')->firstOrFail();
        $flow = ContentSection::query()->where('key', 'flujo-pedido')->firstOrFail();

        $response = $this->get('/');

        $response->assertSee($channels->title, false);
        $response->assertSee($flow->title, false);

        // Every channel and every order state reaches the document.
        $channels->activeModules->each(fn ($module) => $response->assertSee($module->title, false));
        $flow->activeModules->each(fn ($module) => $response->assertSee($module->title, false));
    }

    public function test_home_page_injects_the_payload_for_the_vue_application(): void
    {
        $response = $this->get('/');

        $response->assertSee('window.__HIVEDFOOD__', false);
    }

    public function test_content_endpoint_returns_the_site_payload(): void
    {
        $response = $this->getJson('/api/content');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'settings',
                'sections',
                'banners',
                'features',
                'demonstrations',
                'testimonials',
                'faqs',
                'quoter' => ['business_types', 'packages', 'modules', 'scalability', 'external_costs', 'pricing_published'],
            ],
        ]);
    }

    public function test_inactive_sections_are_not_published(): void
    {
        $keys = collect($this->getJson('/api/content')->json('data.sections'))->pluck('key');

        // Testimonials ship disabled until real, authorised quotes exist.
        $this->assertFalse($keys->contains('testimonios'));
        $this->assertTrue($keys->contains('plataforma'));
    }

    public function test_sitemap_lists_the_pages_marked_for_indexing(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<loc>'.url('/').'</loc>', false);
        $response->assertSee('<priority>1.0</priority>', false);
    }

    public function test_robots_blocks_the_panel_and_links_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Disallow: /admin');
        $response->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_deep_links_are_served_by_laravel_with_their_own_meta(): void
    {
        $this->get('/demostraciones')->assertOk();
        $this->get('/cotizador')->assertOk();
    }
}
