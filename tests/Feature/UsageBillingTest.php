<?php

namespace Tests\Feature;

use App\Models\BillingModel;
use App\Models\OrderPriceTier;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\Quotation;
use App\Models\SiteSetting;
use App\Services\QuotationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cobro por pedido con tramos escalonados.
 *
 * La regla que ordena todo: cada pedido paga el precio del tramo en el que
 * cae. Pasar de 500 a 501 pedidos encarece el pedido 501 y abarata los que
 * vengan después, pero nunca recalcula los 500 anteriores.
 */
class UsageBillingTest extends TestCase
{
    use RefreshDatabase;

    private PricingPlan $package;

    private BillingModel $usage;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::query()->create(['key' => 'pricing_published', 'type' => 'boolean', 'value' => '1', 'group' => 'pricing']);
        SiteSetting::flush();

        $this->package = PricingPlan::query()->create([
            'name' => 'Paquete base',
            'slug' => 'paquete-base',
            'level' => 1,
            'setup_price' => 300,
            'monthly_price' => 50,
            'currency' => 'USD',
            'included_item_slugs' => [],
            'is_active' => true,
        ]);

        PricingItem::query()->create([
            'name' => 'Local adicional',
            'slug' => 'sucursal-adicional',
            'group' => 'scalability',
            'unit_kind' => 'branch',
            'included_quantity' => 1,
            'min_quantity' => 1,
            'max_quantity' => 200,
            'setup_price' => 0,
            'monthly_price' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->usage = $this->billingModel();
    }

    private function billingModel(array $attributes = [], ?array $tiers = null): BillingModel
    {
        static $sequence = 0;
        $sequence++;

        $model = BillingModel::query()->create(array_merge([
            'name' => 'Mensualidad más consumo',
            'slug' => 'uso-'.$sequence,
            'type' => 'per_order',
            'charges_monthly' => true,
            'charges_usage' => true,
            'volume_basis' => 'branch',
            'included_orders' => 0,
            'is_active' => true,
        ], $attributes));

        foreach ($tiers ?? [
            ['from_orders' => 1, 'to_orders' => 500, 'price_per_order' => 0.25],
            ['from_orders' => 501, 'to_orders' => 1000, 'price_per_order' => 0.15],
            ['from_orders' => 1001, 'to_orders' => null, 'price_per_order' => 0.10],
        ] as $tier) {
            OrderPriceTier::query()->create($tier + ['billing_model_id' => $model->id, 'is_active' => true]);
        }

        return $model;
    }

    /** @return array<string, mixed> */
    private function calculate(array $input = []): array
    {
        return app(QuotationCalculator::class)->calculate(array_merge([
            'plan_id' => $this->package->id,
            'billing_model_id' => $this->usage->id,
            'scalability' => ['branches' => 1],
        ], $input));
    }

    /** @return array<int, array{int, float}> */
    public static function volumes(): array
    {
        return [
            'sin pedidos' => [0, 0.0],
            'cien' => [100, 25.0],
            'justo en el borde' => [500, 125.0],
            // El pedido 501 cuesta 15 centavos; los 500 anteriores siguen a 25.
            'uno más que el borde' => [501, 125.15],
            'dos más que el borde' => [502, 125.30],
            'seiscientos' => [600, 140.0],
            'mil' => [1000, 200.0],
            'mil uno' => [1001, 200.10],
            'mil quinientos' => [1500, 250.0],
        ];
    }

    #[DataProvider('volumes')]
    public function test_each_order_pays_the_price_of_its_own_tier(int $orders, float $expected): void
    {
        $result = $this->calculate(['orders_per_branch' => $orders]);

        $this->assertSame($expected, $result['usage_monthly']);
    }

    public function test_crossing_a_tier_never_makes_the_bill_go_down(): void
    {
        $previous = 0.0;

        // La factura tiene que crecer pedido a pedido alrededor del borde.
        foreach (range(495, 510) as $orders) {
            $amount = $this->calculate(['orders_per_branch' => $orders])['usage_monthly'];

            $this->assertGreaterThan($previous, $amount, "El consumo bajó al pasar a {$orders} pedidos.");
            $previous = $amount;
        }
    }

    public function test_one_extra_order_only_costs_the_price_of_its_tier(): void
    {
        $atEdge = $this->calculate(['orders_per_branch' => 500])['usage_monthly'];
        $overEdge = $this->calculate(['orders_per_branch' => 501])['usage_monthly'];

        $this->assertSame(0.15, round($overEdge - $atEdge, 2));
    }

    public function test_the_breakdown_explains_how_the_orders_were_split(): void
    {
        $usage = $this->calculate(['orders_per_branch' => 600])['usage'];

        $this->assertCount(2, $usage['tiers']);
        $this->assertSame(500, $usage['tiers'][0]['orders']);
        $this->assertSame(125.0, $usage['tiers'][0]['amount']);
        $this->assertSame(100, $usage['tiers'][1]['orders']);
        $this->assertSame(15.0, $usage['tiers'][1]['amount']);
        $this->assertSame(0.2333, $usage['average_per_order']);
    }

    public function test_each_branch_runs_its_own_ladder(): void
    {
        $result = $this->calculate([
            'orders_per_branch' => 600,
            'scalability' => ['branches' => 3],
        ]);

        // Tres locales de 600 pedidos: cada uno recorre su propia escalera.
        $this->assertSame(420.0, $result['usage_monthly']);
        $this->assertSame(1800, $result['usage']['total_orders']);
        $this->assertSame('branch', $result['usage']['basis']);
    }

    public function test_the_account_wide_basis_adds_up_every_branch(): void
    {
        $this->usage->forceFill(['volume_basis' => 'total'])->save();

        $result = $this->calculate([
            'orders_per_branch' => 600,
            'scalability' => ['branches' => 3],
        ]);

        // 1.800 pedidos: 500 a 0,25 + 500 a 0,15 + 800 a 0,10.
        $this->assertSame(280.0, $result['usage_monthly']);
        $this->assertSame('total', $result['usage']['basis']);
    }

    public function test_included_orders_are_free_and_consume_the_first_slots(): void
    {
        $this->usage->forceFill(['included_orders' => 100])->save();

        $result = $this->calculate(['orders_per_branch' => 600]);

        // Los 100 incluidos no se cobran; del 101 al 500 van a 0,25.
        $this->assertSame(400 * 0.25 + 100 * 0.15, $result['usage_monthly']);
        $this->assertSame(500, $result['usage']['chargeable_orders']);
    }

    public function test_a_monthly_minimum_lifts_a_very_small_month(): void
    {
        $this->usage->forceFill(['min_monthly' => 40])->save();

        $result = $this->calculate(['orders_per_branch' => 100]);

        $this->assertSame(40.0, $result['usage_monthly']);
        $this->assertTrue($result['usage']['minimum_applied']);
        $this->assertSame(25.0, $result['usage']['subtotal']);
    }

    public function test_the_usage_never_touches_the_fixed_monthly(): void
    {
        $result = $this->calculate(['orders_per_branch' => 600]);

        $this->assertSame(300.0, $result['setup_total']);
        $this->assertSame(50.0, $result['monthly_total']);
        $this->assertSame(140.0, $result['usage_monthly']);
        $this->assertSame(190.0, $result['monthly_estimated_total']);
        // El consumo se factura vencido: no entra en el primer pago.
        $this->assertSame(350.0, $result['first_payment']);
        $this->assertSame(300.0 + (190.0 * 12), $result['year_projection']);
    }

    public function test_a_modality_without_a_fixed_fee_charges_only_the_usage(): void
    {
        $this->usage->forceFill(['charges_monthly' => false])->save();

        $result = $this->calculate(['orders_per_branch' => 600]);

        $this->assertSame(0.0, $result['monthly_total']);
        $this->assertSame(140.0, $result['usage_monthly']);
        // La implementación se sigue cobrando: no es parte del consumo.
        $this->assertSame(300.0, $result['setup_total']);
    }

    public function test_the_fixed_modality_ignores_the_declared_volume(): void
    {
        $fixed = BillingModel::query()->create([
            'name' => 'Mensualidad fija',
            'slug' => 'solo-mensual',
            'type' => 'monthly',
            'charges_monthly' => true,
            'charges_usage' => false,
            'is_active' => true,
        ]);

        $result = $this->calculate(['billing_model_id' => $fixed->id, 'orders_per_branch' => 50000]);

        $this->assertNull($result['usage']);
        $this->assertNull($result['usage_monthly']);
        $this->assertSame(50.0, $result['monthly_estimated_total']);
    }

    public function test_orders_outside_every_tier_are_not_invented(): void
    {
        // Un hueco entre tramos deja pedidos sin tarifa.
        $model = $this->billingModel(['slug' => 'con-hueco'], [
            ['from_orders' => 1, 'to_orders' => 100, 'price_per_order' => 0.25],
            ['from_orders' => 201, 'to_orders' => null, 'price_per_order' => 0.10],
        ]);

        $result = $this->calculate(['billing_model_id' => $model->id, 'orders_per_branch' => 150]);

        $this->assertFalse($result['usage']['is_priced']);
        $this->assertSame(50, $result['usage']['uncovered_orders']);
        $this->assertSame(0.0, $result['usage_monthly']);
        $this->assertFalse($result['is_priced']);
    }

    public function test_a_modality_without_tiers_cannot_quote_the_usage(): void
    {
        $model = BillingModel::query()->create([
            'name' => 'Consumo sin tarifas',
            'slug' => 'sin-tramos',
            'type' => 'per_order',
            'charges_monthly' => true,
            'charges_usage' => true,
            'is_active' => true,
        ]);

        $result = $this->calculate(['billing_model_id' => $model->id, 'orders_per_branch' => 300]);

        $this->assertFalse($result['usage']['is_priced']);
        $this->assertFalse($result['is_priced']);
    }

    public function test_the_browser_cannot_dictate_the_price_per_order(): void
    {
        $response = $this->postJson('/api/quotations/calculate', [
            'plan_id' => $this->package->id,
            'billing_model_id' => $this->usage->id,
            'orders_per_branch' => 600,
            'usage_monthly' => 1,
            'usage' => ['monthly' => 1, 'tiers' => []],
        ]);

        $response->assertOk();
        $this->assertEquals(140, $response->json('data.usage_monthly'));
    }

    public function test_an_inactive_modality_is_rejected(): void
    {
        $this->usage->forceFill(['is_active' => false])->save();

        $this->postJson('/api/quotations/calculate', [
            'plan_id' => $this->package->id,
            'billing_model_id' => $this->usage->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('billing_model_id');
    }

    public function test_a_stored_quotation_keeps_the_usage_and_its_tariff(): void
    {
        $response = $this->postJson('/api/quotations', [
            'plan_id' => $this->package->id,
            'billing_model_id' => $this->usage->id,
            'orders_per_branch' => 600,
            'contact' => ['name' => 'Ana Pérez', 'email' => 'ana@burgerhouse.test'],
            'elapsed' => 9000,
        ])->assertCreated();

        $quotation = Quotation::query()->firstOrFail();

        $this->assertSame($this->usage->id, $quotation->billing_model_id);
        $this->assertSame('Mensualidad más consumo', $quotation->billing_model_label);
        $this->assertSame(140.0, round((float) $quotation->usage_monthly, 2));
        $this->assertSame(600, $quotation->payload['usage']['orders_per_branch']);

        // La fotografía guarda los tramos con los que se cotizó.
        $snapshot = collect($quotation->pricing_snapshot['billing_models'])->firstWhere('id', $this->usage->id);

        $this->assertCount(3, $snapshot['active_tiers']);
        $this->assertEquals(0.25, $snapshot['active_tiers'][0]['price_per_order']);
        $this->assertEquals(140, $response->json('data.usage_monthly'));
    }

    public function test_a_tier_change_does_not_alter_an_old_quotation(): void
    {
        $this->postJson('/api/quotations', [
            'plan_id' => $this->package->id,
            'billing_model_id' => $this->usage->id,
            'orders_per_branch' => 600,
            'contact' => ['name' => 'Ana Pérez', 'email' => 'ana@burgerhouse.test'],
            'elapsed' => 9000,
        ])->assertCreated();

        OrderPriceTier::query()->where('billing_model_id', $this->usage->id)->update(['price_per_order' => 0.40]);

        $quotation = Quotation::query()->firstOrFail();

        $this->assertSame(140.0, round((float) $quotation->usage_monthly, 2));
        $this->assertEquals(
            0.25,
            collect($quotation->pricing_snapshot['billing_models'])
                ->firstWhere('id', $this->usage->id)['active_tiers'][0]['price_per_order']
        );
    }
}
