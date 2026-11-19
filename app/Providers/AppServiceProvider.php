<?php

namespace App\Providers;

use App\Services\SiteContentService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every document that renders brand colours receives the palette
        // configured in the panel, so changing it needs no asset rebuild.
        View::composer(
            ['public.home', 'admin.panel', 'admin.login'],
            fn ($view) => $view->with('themeVariables', app(SiteContentService::class)->cssVariables()),
        );
    }
}
