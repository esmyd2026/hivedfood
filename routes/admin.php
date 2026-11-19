<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BillingModelController;
use App\Http\Controllers\Admin\BusinessTypeController;
use App\Http\Controllers\Admin\ContentModuleController;
use App\Http\Controllers\Admin\ContentSectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemonstrationController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderPriceTierController;
use App\Http\Controllers\Admin\PricingAuditController;
use App\Http\Controllers\Admin\PricingDiscountController;
use App\Http\Controllers\Admin\PricingItemController;
use App\Http\Controllers\Admin\PricingPlanController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API del panel administrativo
|--------------------------------------------------------------------------
|
| Loaded from routes/web.php inside the "admin" prefix with the auth + admin
| middleware already applied, so these endpoints use the session guard and
| the standard CSRF protection.
|
*/

Route::prefix('api')->name('admin.api.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('me', function () {
        $user = request()->user();

        return response()->json(['data' => ['name' => $user->name, 'email' => $user->email]]);
    })->name('me');

    $resources = [
        'banners' => BannerController::class,
        'secciones' => ContentSectionController::class,
        'modulos' => ContentModuleController::class,
        'funcionalidades' => FeatureController::class,
        'demostraciones' => DemonstrationController::class,
        'testimonios' => TestimonialController::class,
        'faqs' => FaqController::class,
        'tipos-negocio' => BusinessTypeController::class,
        'formas-cobro' => BillingModelController::class,
        'tramos-pedido' => OrderPriceTierController::class,
        'planes' => PricingPlanController::class,
        'items-precio' => PricingItemController::class,
        'descuentos' => PricingDiscountController::class,
        'seo' => SeoSettingController::class,
    ];

    foreach ($resources as $uri => $controller) {
        Route::get($uri, [$controller, 'index'])->name($uri.'.index');
        Route::post($uri, [$controller, 'store'])->name($uri.'.store');
        Route::post($uri.'/reorder', [$controller, 'reorder'])->name($uri.'.reorder');
        Route::get($uri.'/{id}', [$controller, 'show'])->whereNumber('id')->name($uri.'.show');
        // POST (not PUT) so multipart uploads with _method spoofing are simple.
        Route::post($uri.'/{id}', [$controller, 'update'])->whereNumber('id')->name($uri.'.update');
        Route::post($uri.'/{id}/toggle', [$controller, 'toggle'])->whereNumber('id')->name($uri.'.toggle');
        Route::delete($uri.'/{id}', [$controller, 'destroy'])->whereNumber('id')->name($uri.'.destroy');
    }

    // Biblioteca de imágenes
    Route::get('medios', [MediaController::class, 'index'])->name('media.index');
    Route::post('medios', [MediaController::class, 'store'])->name('media.store');
    Route::post('medios/sincronizar', [MediaController::class, 'sync'])->name('media.sync');
    Route::get('medios/{media}', [MediaController::class, 'show'])->whereNumber('media')->name('media.show');
    Route::post('medios/{media}', [MediaController::class, 'update'])->whereNumber('media')->name('media.update');
    Route::post('medios/{media}/reemplazar', [MediaController::class, 'replace'])->whereNumber('media')->name('media.replace');
    Route::delete('medios/{media}', [MediaController::class, 'destroy'])->whereNumber('media')->name('media.destroy');

    // Leads
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::post('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    // Cotizaciones
    // Historial del tarifario: solo lectura.
    Route::get('historial-precios', [PricingAuditController::class, 'index'])->name('pricing-audits.index');

    Route::get('cotizaciones', [QuotationController::class, 'index'])->name('quotations.index');
    Route::get('cotizaciones/{quotation:id}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::post('cotizaciones/{quotation:id}', [QuotationController::class, 'update'])->name('quotations.update');
    Route::post('cotizaciones/{quotation:id}/reenviar', [QuotationController::class, 'resend'])->name('quotations.resend');
    Route::post('cotizaciones/{quotation:id}/duplicar', [QuotationController::class, 'duplicate'])->name('quotations.duplicate');
    Route::post('cotizaciones/{quotation:id}/implementacion', [QuotationController::class, 'requestImplementation'])->name('quotations.implementation');
    Route::get('cotizaciones/{quotation:id}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');
    Route::delete('cotizaciones/{quotation:id}', [QuotationController::class, 'destroy'])->name('quotations.destroy');

    // Configuración general
    Route::get('configuracion', [SettingController::class, 'index'])->name('settings.index');
    Route::post('configuracion', [SettingController::class, 'update'])->name('settings.update');
});
