<?php

namespace App\Http\Controllers;

use App\Models\Demonstration;
use App\Models\SeoSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = SeoSetting::query()
            ->where('in_sitemap', true)
            ->get()
            ->map(fn (SeoSetting $seo) => [
                'loc' => $seo->canonical_url ?: url($seo->path ?: '/'),
                'lastmod' => ($seo->updated_at ?? now())->toAtomString(),
                'changefreq' => $seo->sitemap_changefreq,
                'priority' => number_format($seo->sitemap_priority, 1),
            ]);

        $demoLastmod = Demonstration::query()->published()->max('updated_at');

        if ($demoLastmod && $urls->isNotEmpty()) {
            $urls = $urls->map(function (array $url) use ($demoLastmod) {
                if (str_ends_with($url['loc'], '/demostraciones')) {
                    $url['lastmod'] = Carbon::parse($demoLastmod)->toAtomString();
                }

                return $url;
            });
        }

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
