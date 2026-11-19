<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\SeoSetting;
use App\Models\SiteSetting;

/**
 * Resolves administrable meta tags and structured data for a public page.
 */
class SeoService
{
    /** @return array<string, mixed> */
    public function forPage(string $pageKey, array $overrides = []): array
    {
        $seo = SeoSetting::query()->where('page_key', $pageKey)->first();
        $siteName = (string) SiteSetting::get('site_name', 'HIVEDFOOD');
        $defaultImage = media_url(SiteSetting::get('og_image') ?: SiteSetting::get('logo'));

        $title = $seo?->meta_title ?: $siteName;
        $description = $seo?->meta_description ?: (string) SiteSetting::get('tagline', '');
        $canonical = $seo?->canonical_url ?: url($seo?->path ?: '/');

        $data = [
            'title' => $title,
            'description' => $description,
            'keywords' => $seo?->meta_keywords,
            'canonical' => $canonical,
            'robots' => $seo?->robots ?: 'index,follow',
            'og_title' => $seo?->og_title ?: $title,
            'og_description' => $seo?->og_description ?: $description,
            'og_image' => media_url($seo?->og_image) ?: $defaultImage,
            'og_type' => $seo?->og_type ?: 'website',
            'twitter_card' => $seo?->twitter_card ?: 'summary_large_image',
            'site_name' => $siteName,
            'locale' => str_replace('-', '_', (string) config('app.locale', 'es')).'_ES',
        ];

        return array_merge($data, array_filter($overrides, fn ($v) => $v !== null));
    }

    /**
     * JSON-LD graph for the home page: organisation, software product and FAQ.
     *
     * @return array<string, mixed>
     */
    public function homeStructuredData(): array
    {
        $siteName = (string) SiteSetting::get('site_name', 'HIVEDFOOD');
        $logo = media_url(SiteSetting::get('logo'));
        $custom = SeoSetting::query()->where('page_key', 'home')->value('structured_data');

        $sameAs = collect([
            SiteSetting::get('facebook_url'),
            SiteSetting::get('instagram_url'),
            SiteSetting::get('linkedin_url'),
            SiteSetting::get('tiktok_url'),
            SiteSetting::get('youtube_url'),
        ])->filter()->values()->all();

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => $siteName,
                'url' => url('/'),
                'logo' => $logo,
                'description' => (string) SiteSetting::get('tagline', ''),
                'email' => SiteSetting::get('email'),
                'telephone' => SiteSetting::get('phone'),
                'sameAs' => $sameAs,
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => $siteName,
                'publisher' => ['@id' => url('/').'#organization'],
                'inLanguage' => 'es',
            ],
            [
                '@type' => 'SoftwareApplication',
                'name' => $siteName,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'description' => (string) SiteSetting::get(
                    'seo_product_description',
                    'Plataforma SaaS de comercio electrónico para restaurantes y negocios gastronómicos.'
                ),
                'url' => url('/'),
            ],
        ];

        $faqs = Faq::query()->active()->ordered()->get();

        if ($faqs->isNotEmpty()) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn (Faq $faq) => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($faq->answer),
                    ],
                ])->values()->all(),
            ];
        }

        if (is_array($custom) && $custom !== []) {
            $graph[] = $custom;
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
