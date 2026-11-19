<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\BillingModel;
use App\Models\BusinessType;
use App\Models\ContentSection;
use App\Models\Demonstration;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Support\Collection;

/**
 * Builds the payload consumed by the public Vue application.
 *
 * The same payload is injected into the Blade shell (so the first paint needs
 * no round trip) and exposed through the REST API.
 */
class SiteContentService
{
    /** @return array<string, mixed> */
    public function homePayload(): array
    {
        return [
            'settings' => $this->publicSettings(),
            'sections' => $this->sections(),
            'banners' => $this->banners(),
            'features' => $this->features(),
            'demonstrations' => $this->demonstrations(),
            'testimonials' => $this->testimonials(),
            'faqs' => $this->faqs(),
            'quoter' => $this->quoterConfig(),
        ];
    }

    /** @return array<string, mixed> */
    public function publicSettings(): array
    {
        $values = SiteSetting::all_values();

        // Commercial pricing is deliberately excluded here; totals only ever
        // come back from the calculator endpoint.
        $keys = [
            'site_name', 'tagline', 'logo', 'logo_light', 'favicon', 'email', 'phone',
            'whatsapp_number', 'whatsapp_message', 'address', 'city', 'country',
            'facebook_url', 'instagram_url', 'linkedin_url', 'tiktok_url', 'youtube_url',
            'color_charcoal', 'color_honey', 'color_ivory', 'color_coral',
            'footer_text', 'legal_name', 'schedule', 'demo_calendar_url',
        ];

        $out = [];

        foreach ($keys as $key) {
            $value = $values[$key] ?? null;
            $out[$key] = in_array($key, ['logo', 'logo_light', 'favicon'], true)
                ? media_url($value)
                : $value;
        }

        $out['whatsapp_url'] = $this->whatsappUrl($values);
        $out['nav'] = $values['nav_links'] ?? [];
        $out['footer_links'] = $values['footer_links'] ?? [];

        // Every label the interface shows is administrable: the whole "textos"
        // group travels to the frontend, which falls back to its own defaults
        // for any key that is missing or left blank.
        $out['texts'] = SiteSetting::query()
            ->where('group', 'textos')
            ->get()
            ->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->decoded()])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        $out['palette'] = array_filter([
            'charcoal' => $values['color_charcoal'] ?? null,
            'honey' => $values['color_honey'] ?? null,
            'ivory' => $values['color_ivory'] ?? null,
            'coral' => $values['color_coral'] ?? null,
        ]);

        $out['preview_palettes'] = $values['preview_palettes'] ?? [];

        return $out;
    }

    /**
     * Brand colours as CSS custom properties, so the palette configured in the
     * panel overrides the compiled theme without rebuilding any asset.
     *
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        $values = SiteSetting::all_values();

        $map = [
            '--color-charcoal' => 'color_charcoal',
            '--color-honey' => 'color_honey',
            '--color-ivory' => 'color_ivory',
            '--color-coral' => 'color_coral',
        ];

        $variables = [];

        foreach ($map as $variable => $key) {
            $value = trim((string) ($values[$key] ?? ''));

            // Only plain hex colours are emitted, never raw input.
            if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
                $variables[$variable] = $value;
            }
        }

        return $variables;
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(): array
    {
        return ContentSection::query()
            ->active()
            ->ordered()
            ->with('activeModules')
            ->get()
            ->map(fn (ContentSection $section) => [
                'id' => $section->id,
                'key' => $section->key,
                'component' => $section->component,
                'eyebrow' => $section->eyebrow,
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'description' => $section->description,
                'image' => media_url($section->image),
                'buttons' => $section->buttons ?? [],
                'settings' => $section->settings ?? [],
                'modules' => $section->activeModules->map(fn ($module) => [
                    'id' => $module->id,
                    'layout' => $module->layout,
                    'eyebrow' => $module->eyebrow,
                    'title' => $module->title,
                    'subtitle' => $module->subtitle,
                    'description' => $module->description,
                    'image' => media_url($module->image),
                    'image_alt' => $module->image_alt,
                    'icon' => $module->icon,
                    'value' => $module->value,
                    'bullets' => $module->bullets ?? [],
                    'buttons' => $module->buttons ?? [],
                    'settings' => $module->settings ?? [],
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function banners(): array
    {
        return Banner::query()
            ->published()
            ->ordered()
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'eyebrow' => $banner->eyebrow,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'description' => $banner->description,
                'cta_text' => $banner->cta_text,
                'cta_url' => $banner->cta_url,
                'cta_secondary_text' => $banner->cta_secondary_text,
                'cta_secondary_url' => $banner->cta_secondary_url,
                'image_desktop' => media_url($banner->image_desktop),
                'image_mobile' => media_url($banner->image_mobile ?: $banner->image_desktop),
                'image_alt' => $banner->image_alt ?: $banner->title,
                'theme' => $banner->theme,
                'align' => $banner->align,
                'transition' => $banner->transition,
                'duration' => $banner->duration,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function features(): array
    {
        return Feature::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (Feature $feature) => [
                'id' => $feature->id,
                'name' => $feature->name,
                'slug' => $feature->slug,
                'category' => $feature->category,
                'short_description' => $feature->short_description,
                'description' => $feature->description,
                'icon' => $feature->icon,
                'image' => media_url($feature->image),
                'bullets' => $feature->bullets ?? [],
                'is_highlighted' => $feature->is_highlighted,
                'is_available' => $feature->is_available,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function demonstrations(): array
    {
        return Demonstration::query()
            ->published()
            ->ordered()
            ->get()
            ->map(fn (Demonstration $demo) => [
                'id' => $demo->id,
                'business_name' => $demo->business_name,
                'slug' => $demo->slug,
                'category' => $demo->category,
                'logo' => media_url($demo->logo),
                'cover_image' => media_url($demo->cover_image),
                'cover_alt' => $demo->cover_alt ?: $demo->business_name,
                'description' => $demo->description,
                'demo_url' => $demo->demo_url,
                'highlights' => $demo->highlights ?? [],
                'screenshots' => collect($demo->screenshots ?? [])
                    ->map(fn ($shot) => is_array($shot)
                        ? ['url' => media_url($shot['url'] ?? null), 'alt' => $shot['alt'] ?? $demo->business_name]
                        : ['url' => media_url($shot), 'alt' => $demo->business_name])
                    ->filter(fn ($shot) => filled($shot['url']))
                    ->values()
                    ->all(),
                'accent_color' => $demo->accent_color,
                'is_featured' => $demo->is_featured,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function testimonials(): array
    {
        return Testimonial::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (Testimonial $t) => [
                'id' => $t->id,
                'author' => $t->author,
                'role' => $t->role,
                'business' => $t->business,
                'avatar' => media_url($t->avatar),
                'quote' => $t->quote,
                'rating' => $t->rating,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function faqs(): array
    {
        return Faq::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (Faq $faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function quoterConfig(): array
    {
        $published = (bool) SiteSetting::get('pricing_published', false);

        return [
            'pricing_published' => $published,
            'currency' => SiteSetting::get('pricing_currency', 'USD'),
            'currency_symbol' => SiteSetting::get('pricing_currency_symbol', '$'),
            'projection_months' => (int) SiteSetting::get('quotation_projection_months', 12),
            'unpriced_message' => SiteSetting::get(
                'pricing_unpriced_message',
                'Nuestro equipo comercial preparará una propuesta con los precios finales para tu configuración.'
            ),
            'module_categories' => is_array(SiteSetting::get('module_categories'))
                ? SiteSetting::get('module_categories')
                : [],
            'external_costs' => is_array(SiteSetting::get('external_costs'))
                ? SiteSetting::get('external_costs')
                : [],
            'business_types' => BusinessType::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (BusinessType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'icon' => $type->icon,
                    'short_description' => $type->short_description,
                    'allow_free_text' => $type->allow_free_text,
                ])
                ->values()
                ->all(),
            'packages' => PricingPlan::query()
                ->active()
                ->current()
                ->ordered()
                ->get()
                ->map(fn (PricingPlan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'level' => $plan->level,
                    'headline' => $plan->headline,
                    'description' => $plan->description,
                    'icon' => $plan->icon,
                    'included' => $plan->included ?? [],
                    'included_item_slugs' => $plan->included_item_slugs ?? [],
                    'is_recommended' => $plan->is_recommended,
                    'setup_price' => $published ? $this->money($plan->setup_price) : null,
                    'monthly_price' => $published ? $this->money($plan->monthly_price) : null,
                    'has_pricing' => $published && $plan->hasPricing(),
                    'currency' => $plan->currency,
                ])
                ->values()
                ->all(),
            'modules' => $this->catalogueItems('module', $published),
            'scalability' => $this->catalogueItems('scalability', $published),
            'billing_models' => $this->billingModels($published),
        ];
    }

    /**
     * Modalidades de cobro publicadas.
     *
     * Solo deciden si se cobra la mensualidad fija y si además se cobra el
     * consumo por pedidos; los tramos viajan para poder explicarlos en la web.
     *
     * @return array<int, array<string, mixed>>
     */
    private function billingModels(bool $published): array
    {
        return BillingModel::query()
            ->active()
            ->current()
            ->ordered()
            ->with('activeTiers')
            ->get()
            ->map(fn (BillingModel $model) => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'type' => $model->type,
                'headline' => $model->headline,
                'description' => $model->description,
                'icon' => $model->icon,
                'charges_monthly' => $model->charges_monthly,
                'charges_usage' => $model->charges_usage,
                'included_orders' => $model->included_orders,
                'volume_basis' => $model->volume_basis,
                'min_monthly' => $published ? $this->money($model->min_monthly) : null,
                'included' => $model->included ?? [],
                'conditions' => $model->conditions ?? [],
                'is_recommended' => $model->is_recommended,
                'has_pricing' => $published && $model->hasPricing(),
                'tiers' => $model->activeTiers
                    ->map(fn ($tier) => [
                        'id' => $tier->id,
                        'label' => $tier->label ?: $tier->range(),
                        'range' => $tier->range(),
                        'from_orders' => $tier->from_orders,
                        'to_orders' => $tier->to_orders,
                        'price_per_order' => $published ? (float) $tier->price_per_order : null,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Elementos del catálogo comercial listos para el cotizador.
     *
     * @return array<int, array<string, mixed>>
     */
    private function catalogueItems(string $group, bool $published): array
    {
        return PricingItem::query()
            ->active()
            ->current()
            ->group($group)
            ->ordered()
            ->get()
            ->map(fn (PricingItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'category' => $item->category,
                'icon' => $item->icon,
                'short_description' => $item->short_description,
                'description' => $item->description,
                'benefits' => $item->benefits ?? [],
                'unit' => $item->unit,
                'unit_kind' => $item->unit_kind,
                'min_quantity' => $item->min_quantity,
                'max_quantity' => $item->max_quantity,
                'included_quantity' => $item->included_quantity,
                'requires' => $item->requires ?? [],
                'requires_any' => $item->requires_any ?? [],
                'notes' => $item->notes ?? [],
                // Los que no se venden sueltos llegan dentro de un paquete.
                'is_available' => $item->is_available,
                'setup_price' => $published ? $this->money($item->setup_price) : null,
                'monthly_price' => $published ? $this->money($item->monthly_price) : null,
                'has_pricing' => $published && $item->hasPricing(),
                'currency' => $item->currency,
            ])
            ->values()
            ->all();
    }

    private function money(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /** @param array<string, mixed> $values */
    private function whatsappUrl(array $values): ?string
    {
        $number = preg_replace('/\D+/', '', (string) ($values['whatsapp_number'] ?? ''));

        if (blank($number)) {
            return null;
        }

        $message = (string) ($values['whatsapp_message'] ?? '');

        return 'https://wa.me/'.$number.($message !== '' ? '?text='.rawurlencode($message) : '');
    }

    /** @return Collection<int, ContentSection> */
    public function adminSections(): Collection
    {
        return ContentSection::query()->ordered()->with('modules')->get();
    }
}
