<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ContentSection;
use App\Models\Demonstration;
use App\Models\Lead;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\Quotation;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $thirtyDaysAgo = now()->subDays(30);

        return response()->json([
            'data' => [
                'summary' => [
                    'leads_total' => Lead::query()->count(),
                    'leads_pending' => Lead::query()->pending()->count(),
                    'leads_last_30' => Lead::query()->where('created_at', '>=', $thirtyDaysAgo)->count(),
                    'quotations_total' => Quotation::query()->count(),
                    'quotations_last_30' => Quotation::query()->where('created_at', '>=', $thirtyDaysAgo)->count(),
                    'quotations_open' => Quotation::query()->whereIn('status', ['new', 'sent', 'negotiating'])->count(),
                    'banners_active' => Banner::query()->published()->count(),
                    'sections_active' => ContentSection::query()->active()->count(),
                    'demos_published' => Demonstration::query()->published()->count(),
                ],
                'leads_by_status' => Lead::query()
                    ->select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status'),
                'leads_by_source' => Lead::query()
                    ->select('source', DB::raw('count(*) as total'))
                    ->groupBy('source')
                    ->pluck('total', 'source'),
                'quotations_by_status' => Quotation::query()
                    ->select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status'),
                'recent_leads' => Lead::query()
                    ->latest()
                    ->limit(6)
                    ->get(['id', 'name', 'company', 'email', 'source', 'status', 'created_at']),
                'recent_quotations' => Quotation::query()
                    ->with('lead:id,name,company')
                    ->latest()
                    ->limit(6)
                    ->get(['id', 'code', 'lead_id', 'plan_label', 'total', 'currency', 'is_priced', 'status', 'created_at']),
                'pricing' => [
                    'published' => (bool) SiteSetting::get('pricing_published', false),
                    'plans_without_price' => PricingPlan::query()
                        ->active()
                        ->whereNull('setup_price')
                        ->whereNull('monthly_price')
                        ->count(),
                    // Un concepto sin ninguna de las dos tarifas sigue pendiente de aprobación.
                    'items_without_price' => PricingItem::query()
                        ->active()
                        ->whereNull('setup_price')
                        ->whereNull('monthly_price')
                        ->count(),
                ],
                'checklist' => $this->checklist(),
            ],
        ]);
    }

    /**
     * Configuration gaps worth surfacing on the dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checklist(): array
    {
        $missingDemoUrls = Demonstration::query()
            ->published()
            ->where(fn ($q) => $q->whereNull('demo_url')->orWhere('demo_url', ''))
            ->count();

        return array_values(array_filter([
            blank(SiteSetting::get('whatsapp_number')) ? [
                'key' => 'whatsapp',
                'label' => 'Configura el número de WhatsApp comercial',
                'route' => '/admin/configuracion',
            ] : null,
            blank(SiteSetting::get('notification_email')) ? [
                'key' => 'notification_email',
                'label' => 'Define el correo que recibe los leads',
                'route' => '/admin/configuracion',
            ] : null,
            $missingDemoUrls > 0 ? [
                'key' => 'demo_urls',
                'label' => "Hay {$missingDemoUrls} demostración(es) publicadas sin enlace",
                'route' => '/admin/demostraciones',
            ] : null,
            ! SiteSetting::get('pricing_published', false) ? [
                'key' => 'pricing',
                'label' => 'Los precios aún no se publican en el cotizador',
                'route' => '/admin/cotizador',
            ] : null,
        ]));
    }
}
