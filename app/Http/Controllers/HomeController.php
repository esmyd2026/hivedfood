<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use App\Services\SiteContentService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        private SiteContentService $content,
        private SeoService $seo,
    ) {}

    public function index(): View
    {
        $payload = $this->content->homePayload();

        return view('public.home', [
            'payload' => $payload,
            'seo' => $this->seo->forPage('home'),
            'structuredData' => $this->seo->homeStructuredData(),
        ]);
    }
}
