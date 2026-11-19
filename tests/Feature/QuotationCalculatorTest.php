<?php

namespace Tests\Feature;

use App\Models\PricingDiscount;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\SiteSetting;
use App\Services\QuotationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Motor de cálculo: paquete + módulos + escalabilidad.
 *
 * Los precios salen siempre de la base de datos; lo que no tiene tarifa
 * aprobada se queda sin importe en lugar de inventarse uno.
 */
class QuotationCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function publishPricing(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['key' => 'pricing_published'],
            ['type' => 'boolean', 'value' => '1', 'group' => 'pricing']
        );
        SiteSetting::flush();
    }

    private function package(array $attributes = []): PricingPlan
    {
        static $sequence = 0;
        $sequence++;

        return PricingPlan::query()->create(array_merge([
            'name' => 'Paquete '.$sequence,
            'slug' => 'paquete-'.$sequence,
            'level' => $sequence,
            'setup_price' => 1000,
            'monthly_price' => 100,
            'currency' => 'USD',
            'included_item_slugs' => [],
            'is_active' => true,
        ], $attributes));
    }

    private function module(array $attributes = []): PricingItem
    {
        static $sequence = 0;
        $sequence++;

        return PricingItem::query()->create(array_merge([
            'name' => 'Módulo '.$sequence,
            'slug' => 'modulo-'.$sequence,
            'group' => 'module',
            'category' => 'operacion',
            'setup_price' => 200,
            'monthly_price' => 20,
            'currency' => 'USD',
            'is_available' => true,
            'is_active' => true,
        ], $attributes));
    }

    private function scalability(array $attributes = []): PricingItem
    {
        return PricingItem::query()->create(array_merge([
            'name' => 'Sucursal adicional',
            'slug' => 'sucursal-adicional',
            'group' => 'scalability',
            'unit' => 'sucursal',
            'unit_kind' => 'branch',
            'included_quantity' => 1,
            'min_quantity' => 1,
            'max_quantity' => 200,
            'setup_price' => 120,
            'monthly_price' => 25,
            'currency' => 'USD',
            'is_available' => true,
            'is_active' => true,
        ], $attributes));
    }

    private function calculate(array $input): array
    {
        return app(QuotationCalculator::class)->calculate($input);
    }

    public function test_totals_are_built_from_database_prices(): void
    {
        $this->publishPricing();

        $package = $this->package();
        $a = $this->module(['setup_price' => 200, 'monthly_price' => 0]);
        $b = $this->module(['setup_price' => 0, 'monthly_price' => 50]);

        $result = $this->calculate([
            'plan_id' => $package->id,
            'items' => [['id' => $a->id], ['id' => $b->id]],
        ]);

        $this->assertTrue($result['is_priced']);
        $this->assertSame(1200.0, $result['setup_subtotal']);
        $this->assertSame(150.0, $result['monthly_subtotal']);
    }

    public function test_the_first_payment_is_the_setup_plus_one_month(): void
    {
        $this->publishPricing();

        $result = $this->calculate([
            'plan_id' => $this->package(['setup_price' => 890, 'monthly_price' => 149])->id,
        ]);

        $this->assertSame(1039.0, $result['first_payment']);
    }

    public function test_the_first_year_projection_covers_twelve_months(): void
    {
        $this->publishPricing();

        $result = $this->calculate([
            'plan_id' => $this->package(['setup_price' => 890, 'monthly_price' => 149])->id,
        ]);

        $this->assertSame(12, $result['projection_months']);
        $this->assertSame(890.0 + (149.0 * 12), $result['year_projection']);
    }

    public function test_the_projection_window_is_administrable(): void
    {
        $this->publishPricing();

        SiteSetting::query()->create([
            'key' => 'quotation_projection_months',
            'type' => 'string',
            'value' => '6',
            'group' => 'pricing',
        ]);
        SiteSetting::flush();

        $result = $this->calculate([
            'plan_id' => $this->package(['setup_price' => 100, 'monthly_price' => 10])->id,
        ]);

        $this->assertSame(6, $result['projection_months']);
        $this->assertSame(160.0, $result['year_projection']);
    }

    public function test_scalability_only_charges_what_exceeds_the_included_quantity(): void
    {
        $this->publishPricing();

        $package = $this->package(['setup_price' => 0, 'monthly_price' => 0]);
        $this->scalability();

        $result = $this->calculate([
            'plan_id' => $package->id,
            'scalability' => ['branches' => 3],
        ]);

        $this->assertSame(240.0, $result['setup_subtotal']);  // 120 × 2
        $this->assertSame(50.0, $result['monthly_subtotal']); // 25 × 2
    }

    public function test_scalability_quantities_are_clamped_to_their_range(): void
    {
        $this->publishPricing();

        $package = $this->package(['setup_price' => 0, 'monthly_price' => 0]);
        $this->scalability(['max_quantity' => 5]);

        $result = $this->calculate([
            'plan_id' => $package->id,
            'scalability' => ['branches' => 900],
        ]);

        $this->assertSame(5, $result['scalability']['branches']['total']);
        $this->assertSame(4, $result['scalability']['branches']['chargeable']);
    }

    public function test_a_module_included_in_the_package_is_listed_but_never_charged(): void
    {
        $this->publishPricing();

        $module = $this->module(['slug' => 'cocina-comandas']);
        $package = $this->package(['included_item_slugs' => ['cocina-comandas']]);

        $result = $this->calculate([
            'plan_id' => $package->id,
            'items' => [['id' => $module->id]],
        ]);

        $line = collect($result['lines'])->firstWhere('slug', 'cocina-comandas');

        $this->assertTrue($line['is_included']);
        $this->assertSame(0.0, $line['setup_price']);
        $this->assertSame(1000.0, $result['setup_total']);
    }

    public function test_a_required_module_is_added_automatically_with_its_reason(): void
    {
        $this->publishPricing();

        $base = $this->module(['name' => 'Chatbot', 'slug' => 'chatbot']);
        $dependent = $this->module([
            'name' => 'Asistente IA',
            'slug' => 'asistente',
            'requires' => ['chatbot'],
        ]);

        $result = $this->calculate([
            'plan_id' => $this->package()->id,
            'items' => [['id' => $dependent->id]],
        ]);

        $autoAdded = collect($result['dependencies']['auto_added']);

        $this->assertTrue($autoAdded->contains('slug', 'chatbot'));
        $this->assertStringContainsString('Asistente IA', $autoAdded->first()['reason']);
        $this->assertNotNull(collect($result['lines'])->firstWhere('pricing_item_id', $base->id));
    }

    public function test_a_conditional_dependency_blocks_the_combination(): void
    {
        $this->publishPricing();

        $this->module(['name' => 'Chatbot', 'slug' => 'chatbot']);
        $module = $this->module(['name' => 'Calificaciones', 'slug' => 'calificaciones', 'requires_any' => ['chatbot']]);

        $this->expectException(ValidationException::class);

        $this->calculate([
            'plan_id' => $this->package()->id,
            'items' => [['id' => $module->id]],
        ]);
    }

    public function test_operational_notes_are_informative_and_do_not_block(): void
    {
        $this->publishPricing();

        $module = $this->module(['notes' => ['Requiere una tablet en el local.']]);

        $result = $this->calculate([
            'plan_id' => $this->package()->id,
            'items' => [['id' => $module->id]],
        ]);

        $this->assertSame('Requiere una tablet en el local.', $result['dependencies']['notes'][0]['note']);
    }

    public function test_automatic_discounts_apply_when_their_conditions_are_met(): void
    {
        $this->publishPricing();

        $package = $this->package(['setup_price' => 1000, 'monthly_price' => null]);
        $modules = [$this->module(['setup_price' => 100]), $this->module(['setup_price' => 100])];

        PricingDiscount::query()->create([
            'name' => 'Configuración completa',
            'type' => 'percentage',
            'value' => 10,
            'applies_to' => 'setup',
            'min_items' => 2,
            'is_automatic' => true,
            'is_active' => true,
        ]);

        $result = $this->calculate([
            'plan_id' => $package->id,
            'items' => collect($modules)->map(fn ($m) => ['id' => $m->id])->all(),
        ]);

        $this->assertSame(1200.0, $result['setup_subtotal']);
        $this->assertSame(120.0, $result['setup_discount']);
        $this->assertSame(1080.0, $result['setup_total']);
    }

    public function test_a_discount_below_its_item_threshold_is_ignored(): void
    {
        $this->publishPricing();

        $package = $this->package(['setup_price' => 1000, 'monthly_price' => null]);

        PricingDiscount::query()->create([
            'name' => 'Configuración completa',
            'type' => 'percentage',
            'value' => 10,
            'applies_to' => 'setup',
            'min_items' => 3,
            'is_automatic' => true,
            'is_active' => true,
        ]);

        $result = $this->calculate(['plan_id' => $package->id]);

        $this->assertSame(0.0, $result['setup_discount']);
        $this->assertSame(1000.0, $result['setup_total']);
    }

    public function test_quotes_are_unpriced_while_the_price_list_is_not_published(): void
    {
        // pricing_published viene en falso por defecto.
        $result = $this->calculate(['plan_id' => $this->package()->id]);

        $this->assertFalse($result['is_priced']);
        $this->assertFalse($result['pricing_published']);
        $this->assertSame(0.0, $result['setup_discount']);
    }

    public function test_quotes_are_unpriced_when_any_line_lacks_an_approved_tariff(): void
    {
        $this->publishPricing();

        $priced = $this->module(['setup_price' => 100, 'monthly_price' => 10]);
        $pending = $this->module(['setup_price' => null, 'monthly_price' => null]);

        $result = $this->calculate([
            'plan_id' => $this->package()->id,
            'items' => [['id' => $priced->id], ['id' => $pending->id]],
        ]);

        $this->assertFalse($result['is_priced']);
        $this->assertTrue($result['has_estimate']);
        $this->assertSame(1, $result['unpriced_count']);
    }

    public function test_modules_that_are_not_sold_separately_are_rejected(): void
    {
        $this->publishPricing();

        $module = $this->module(['is_available' => false]);

        $this->expectException(ValidationException::class);

        $this->calculate([
            'plan_id' => $this->package()->id,
            'items' => [['id' => $module->id]],
        ]);
    }

    public function test_expired_packages_are_rejected(): void
    {
        $package = $this->package(['valid_until' => now()->subDay()->toDateString()]);

        $this->expectException(ValidationException::class);

        $this->calculate(['plan_id' => $package->id]);
    }

    public function test_external_costs_travel_with_the_result(): void
    {
        $this->publishPricing();

        SiteSetting::query()->create([
            'key' => 'external_costs',
            'type' => 'json',
            'group' => 'pricing',
            'value' => json_encode([
                ['title' => 'Meta · WhatsApp', 'description' => 'Se factura según consumo real.'],
            ], JSON_UNESCAPED_UNICODE),
        ]);
        SiteSetting::flush();

        $result = $this->calculate(['plan_id' => $this->package()->id]);

        $this->assertSame('Meta · WhatsApp', $result['external_costs'][0]['title']);
    }
}
