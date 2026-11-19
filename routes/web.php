<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitio público
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Deep links into the one-page site: Laravel still serves the full document
// with its own meta tags, and the Vue app scrolls to the section.
Route::get('/demostraciones', [HomeController::class, 'index'])->name('demos');
Route::get('/cotizador', [HomeController::class, 'index'])->name('quoter');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| API pública (sesión web + CSRF)
|--------------------------------------------------------------------------
*/

Route::prefix('api')->name('api.')->group(function () {
    Route::get('content', [ContentController::class, 'index'])->name('content');
    Route::get('quoter', [ContentController::class, 'quoter'])->name('quoter');

    Route::post('quotations/calculate', [QuotationController::class, 'calculate'])
        ->middleware('throttle:60,1')
        ->name('quotations.calculate');

    Route::post('quotations', [QuotationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('quotations.store');

    Route::get('quotations/{quotation}', [QuotationController::class, 'show'])
        ->name('quotations.show');

    Route::post('quotations/{quotation}/email', [QuotationController::class, 'email'])
        ->middleware('throttle:5,1')
        ->name('quotations.email');

    Route::post('leads', [LeadController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('leads.store');
});

Route::get('cotizaciones/{quotation}/pdf', [QuotationController::class, 'pdf'])
    ->middleware('throttle:30,1')
    ->name('quotations.pdf');

/*
|--------------------------------------------------------------------------
| Panel administrativo
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('admin.login.attempt');
    Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        require __DIR__.'/admin.php';

        // Catch-all for the Vue Router history mode. Registered last so it
        // never shadows the admin API routes above.
        Route::get('/{any?}', [PanelController::class, 'index'])
            ->where('any', '^(?!api).*$')
            ->name('admin.panel');
    });
});
