<?php

namespace Tests\Feature;

use App\Models\BillingModel;
use App\Models\PricingAudit;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\Quotation;
use App\Models\SiteSetting;
use App\Services\QuotationCalculator;
use Database\Seeders\BillingModelSeeder;
use Database\Seeders\PricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Los doce escenarios obligatorios de la especificación comercial.
 *
 * Se ejecutan contra el catálogo real sembrado por PricingSeeder, no contra
 * datos inventados: si mañana cambia un paquete, estas pruebas lo reflejan.
 */
class QuoterValidationCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PricingSeeder::class);
        $this->seed(BillingModelSeeder::class);

        SiteSetting::query()->updateOrCreate(
            ['key' => 'pricing_published'],
            ['type' => 'boolean', 'value' => '1', 'group' => 'pricing']
        );
        SiteSetting::flush();
    }

    private function package(string $slug): PricingPlan
    {
        return PricingPlan::query()->where('slug', $slug)->sole();
    }

    private function module(string $slug): PricingItem
    {
        return PricingItem::query()->where('slug', $slug)->sole();
    }

    /** @param array<string, mixed> $input */
    private function calculate(array $input): array
    {
        return app(QuotationCalculator::class)->calculate($input);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function lines(array $result): Collection
    {
        return collect($result['lines']);
    }

    /** Lo que de verdad se cobra, sin las líneas informativas. */
    private function charged(array $result): Collection
    {
        return $this->lines($result)->reject(fn (array $line) => $line['is_included']);
    }

    // Caso 1 ----------------------------------------------------------------

    public function test_case_1_a_basic_customer_only_pays_for_the_first_package(): void
    {
        $package = $this->package('p1-esencial-whatsapp');

        $result = $this->calculate(['plan_id' => $package->id]);

        $charged = $this->charged($result);

        $this->assertCount(1, $charged, 'Sin módulos extra solo debe cobrarse el paquete.');
        $this->assertSame('package', $charged->first()['group']);
        $this->assertSame(390.0, $result['setup_total']);
        $this->assertSame(69.0, $result['monthly_total']);
    }

    // Caso 2 ----------------------------------------------------------------

    public function test_case_2_a_higher_package_lists_everything_it_includes_without_charging_twice(): void
    {
        $p1 = $this->package('p1-esencial-whatsapp');
        $p2 = $this->package('p2-tienda-web');

        $result = $this->calculate(['plan_id' => $p2->id]);

        $included = $this->lines($result)->where('is_included', true)->pluck('slug');

        // Todo lo de P1 sigue estando dentro de P2.
        foreach ($p1->included_item_slugs as $slug) {
            $this->assertContains($slug, $included, $slug.' debería venir incluido en P2.');
        }

        $this->assertContains('micrositio', $included);
        $this->assertCount(1, $this->charged($result));
        $this->assertSame(590.0, $result['setup_total']);
        $this->assertSame(99.0, $result['monthly_total']);
    }

    // Caso 3 ----------------------------------------------------------------

    public function test_case_3_the_in_store_package_includes_kitchen_tv_kiosk_and_pos(): void
    {
        $result = $this->calculate(['plan_id' => $this->package('p3-operacion-en-local')->id]);

        $included = $this->lines($result)->where('is_included', true)->pluck('slug');

        foreach (['cocina-comandas', 'pantalla-tv', 'kiosco', 'punto-venta-pos'] as $slug) {
            $this->assertContains($slug, $included);
        }

        $this->assertCount(1, $this->charged($result));
    }

    // Caso 4 ----------------------------------------------------------------

    public function test_case_4_the_delivery_package_does_not_recharge_previous_modules(): void
    {
        $p4 = $this->package('p4-delivery');

        // El cliente marca de nuevo módulos que ya trae el paquete.
        $result = $this->calculate([
            'plan_id' => $p4->id,
            'items' => [
                ['id' => $this->module('kiosco')->id],
                ['id' => $this->module('punto-venta-pos')->id],
                ['id' => $this->module('delivery-repartidores')->id],
            ],
        ]);

        $charged = $this->charged($result);

        $this->assertCount(1, $charged, 'Solo el paquete genera cargo.');
        $this->assertSame(1090.0, $result['setup_total']);
        $this->assertSame(189.0, $result['monthly_total']);

        // Y ninguno aparece dos veces en el detalle.
        $slugs = $this->lines($result)->pluck('slug');
        $this->assertSame($slugs->count(), $slugs->unique()->count());
    }

    // Caso 5 ----------------------------------------------------------------

    public function test_case_5_the_omnichannel_package_accumulates_every_previous_module(): void
    {
        $packages = PricingPlan::query()->orderBy('level')->get();
        $p5 = $packages->last();

        $result = $this->calculate(['plan_id' => $p5->id]);
        $included = $this->lines($result)->where('is_included', true)->pluck('slug');

        foreach ($packages as $package) {
            foreach ($package->included_item_slugs as $slug) {
                $this->assertContains($slug, $included, $slug.' debería acumularse en P5.');
            }
        }

        $this->assertCount(1, $this->charged($result));
    }

    // Caso 6 ----------------------------------------------------------------

    public function test_case_6_additional_modules_are_charged_on_top_of_the_package(): void
    {
        $package = $this->package('p1-esencial-whatsapp');
        $invoicing = $this->module('facturacion-electronica');
        $stock = $this->module('inventario-stock');

        $result = $this->calculate([
            'plan_id' => $package->id,
            'items' => [['id' => $invoicing->id], ['id' => $stock->id]],
        ]);

        $charged = $this->charged($result)->keyBy('slug');

        $this->assertTrue($charged->has('facturacion-electronica'));
        $this->assertTrue($charged->has('inventario-stock'));
        $this->assertSame(390.0 + 180.0 + 150.0, $result['setup_total']);
        $this->assertSame(69.0 + 25.0 + 20.0, $result['monthly_total']);
    }

    // Caso 7 ----------------------------------------------------------------

    public function test_case_7_the_ai_assistant_turns_the_chatbot_on_by_itself(): void
    {
        // Paquete que NO trae chatbot, para forzar la dependencia.
        $package = PricingPlan::query()->create([
            'name' => 'Base sin canales',
            'slug' => 'base-sin-canales',
            'level' => 1,
            'setup_price' => 100,
            'monthly_price' => 10,
            'currency' => 'USD',
            'included_item_slugs' => [],
            'is_active' => true,
        ]);

        $result = $this->calculate([
            'plan_id' => $package->id,
            'items' => [['id' => $this->module('asistente-ia')->id]],
        ]);

        $autoAdded = collect($result['dependencies']['auto_added']);

        $this->assertTrue($autoAdded->contains('slug', 'chatbot-whatsapp'));
        $this->assertStringContainsString('Asistente', $autoAdded->firstWhere('slug', 'chatbot-whatsapp')['reason']);

        $chatbot = $this->lines($result)->firstWhere('slug', 'chatbot-whatsapp');

        $this->assertNotNull($chatbot, 'El chatbot debe aparecer en el detalle.');
        $this->assertTrue($chatbot['is_auto_added']);
        // El chatbot no se vende suelto: llega con el paquete, sin cargo propio.
        $this->assertTrue($chatbot['is_included']);
        $this->assertSame(100.0 + 190.0, $result['setup_total']);
    }

    // Caso 8 ----------------------------------------------------------------

    public function test_case_8_ratings_without_a_channel_are_rejected(): void
    {
        $package = PricingPlan::query()->create([
            'name' => 'Base sin canales',
            'slug' => 'base-sin-canales',
            'level' => 1,
            'setup_price' => 100,
            'monthly_price' => 10,
            'currency' => 'USD',
            'included_item_slugs' => [],
            'is_active' => true,
        ]);

        // Calificaciones no se vende suelto, así que la combinación se fuerza
        // desde un módulo que sí lo hace y comparte la misma regla.
        $ratings = $this->module('calificaciones');
        $ratings->forceFill(['is_available' => true])->save();

        $this->expectException(ValidationException::class);

        try {
            $this->calculate([
                'plan_id' => $package->id,
                'items' => [['id' => $ratings->id]],
            ]);
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->first('items');

            // El mensaje nombra los canales que servirían, sea cual sea el
            // nombre comercial que tengan hoy.
            foreach (['chatbot-whatsapp', 'micrositio'] as $slug) {
                $this->assertStringContainsString($this->module($slug)->name, $message);
            }

            throw $e;
        }
    }

    public function test_case_8_the_same_module_is_accepted_once_a_channel_is_present(): void
    {
        $ratings = $this->module('calificaciones');
        $ratings->forceFill(['is_available' => true])->save();

        // P2 ya trae chatbot y micrositio.
        $result = $this->calculate([
            'plan_id' => $this->package('p2-tienda-web')->id,
            'items' => [['id' => $ratings->id]],
        ]);

        $this->assertSame(590.0, $result['setup_total']);
    }

    // Caso 9 ----------------------------------------------------------------

    public function test_case_9_four_branches_are_billed_as_three_extra_ones(): void
    {
        $result = $this->calculate([
            'plan_id' => $this->package('p3-operacion-en-local')->id,
            'scalability' => ['branches' => 4],
        ]);

        $branches = $this->charged($result)->firstWhere('slug', 'sucursal-adicional');

        $this->assertNotNull($branches);
        $this->assertSame(3, $branches['quantity'], 'La primera sucursal va incluida.');
        $this->assertSame(360.0, $branches['setup_price']);   // 120 × 3
        $this->assertSame(75.0, $branches['monthly_price']);  // 25 × 3

        $this->assertSame(4, $result['scalability']['branches']['total']);
        $this->assertSame(1, $result['scalability']['branches']['included']);
        $this->assertSame(3, $result['scalability']['branches']['chargeable']);

        $this->assertSame(890.0 + 360.0, $result['setup_total']);
        $this->assertSame(149.0 + 75.0, $result['monthly_total']);
    }

    public function test_case_9_a_single_branch_adds_no_charge(): void
    {
        $result = $this->calculate([
            'plan_id' => $this->package('p3-operacion-en-local')->id,
            'scalability' => ['branches' => 1],
        ]);

        $this->assertNull($this->charged($result)->firstWhere('slug', 'sucursal-adicional'));
        $this->assertSame(890.0, $result['setup_total']);
    }

    // Caso 10 ---------------------------------------------------------------

    public function test_case_10_extra_whatsapp_numbers_count_only_the_additional_connections(): void
    {
        $result = $this->calculate([
            'plan_id' => $this->package('p1-esencial-whatsapp')->id,
            'scalability' => ['whatsapp_numbers' => 3],
        ]);

        $numbers = $result['scalability']['whatsapp_numbers'];

        $this->assertSame(3, $numbers['total']);
        $this->assertSame(1, $numbers['included']);
        $this->assertSame(2, $numbers['chargeable']);
    }

    public function test_case_10_an_unapproved_tariff_is_quoted_but_never_invented(): void
    {
        // La tarifa de números adicionales está pendiente de aprobación.
        $result = $this->calculate([
            'plan_id' => $this->package('p1-esencial-whatsapp')->id,
            'scalability' => ['whatsapp_numbers' => 3],
        ]);

        $line = $this->charged($result)->firstWhere('slug', 'numero-whatsapp');

        $this->assertNotNull($line);
        $this->assertNull($line['setup_price']);
        $this->assertNull($line['monthly_price']);
        $this->assertFalse($line['is_priced']);

        // La cotización deja de ser cerrada y se avisa cuántas líneas faltan.
        $this->assertFalse($result['is_priced']);
        $this->assertTrue($result['has_estimate']);
        $this->assertSame(1, $result['unpriced_count']);

        // Y el total sigue siendo solo lo que sí tiene precio aprobado.
        $this->assertSame(390.0, $result['setup_total']);
    }

    public function test_case_10_the_tariff_starts_charging_as_soon_as_it_is_approved(): void
    {
        $item = $this->module('numero-whatsapp');
        $item->forceFill(['setup_price' => 40, 'monthly_price' => 12])->save();

        $result = $this->calculate([
            'plan_id' => $this->package('p1-esencial-whatsapp')->id,
            'scalability' => ['whatsapp_numbers' => 3],
        ]);

        $this->assertSame(390.0 + 80.0, $result['setup_total']);
        $this->assertSame(69.0 + 24.0, $result['monthly_total']);
        $this->assertTrue($result['is_priced']);
    }

    // Caso 11 ---------------------------------------------------------------

    public function test_case_11_the_fixed_modality_never_charges_by_order_volume(): void
    {
        $fixed = BillingModel::query()->where('slug', 'mensual')->sole();

        $base = [
            'plan_id' => $this->package('p3-operacion-en-local')->id,
            'billing_model_id' => $fixed->id,
            'scalability' => ['branches' => 4],
        ];

        $small = $this->calculate($base + ['orders_per_branch' => 20]);

        // Un restaurante con miles de pedidos al mes paga exactamente lo mismo.
        $huge = $this->calculate($base + [
            'orders_per_branch' => 50000,
            'catalog' => ['products' => 4000, 'categories' => 300, 'variations' => 9000],
        ]);

        $this->assertSame($small['setup_total'], $huge['setup_total']);
        $this->assertSame($small['monthly_total'], $huge['monthly_total']);
        $this->assertSame($small['year_projection'], $huge['year_projection']);
        $this->assertNull($huge['usage']);
    }

    public function test_case_11_the_volume_only_costs_when_the_customer_picks_that_modality(): void
    {
        $usage = BillingModel::query()->where('slug', 'mensual-mas-uso')->sole();

        $result = $this->calculate([
            'plan_id' => $this->package('p1-esencial-whatsapp')->id,
            'billing_model_id' => $usage->id,
            'orders_per_branch' => 600,
        ]);

        // El cargo fijo no se mueve: el volumen vive en su propia línea.
        $this->assertSame(69.0, $result['monthly_total']);
        $this->assertSame(140.0, $result['usage_monthly']);
        $this->assertSame(209.0, $result['monthly_estimated_total']);
    }

    public function test_case_11_no_package_or_module_is_limited_by_order_volume(): void
    {
        $this->assertSame(
            0,
            PricingItem::query()->active()->where('unit', 'like', '%pedido%')->count(),
            'Ningún concepto del catálogo puede facturarse por pedido.'
        );

        $text = json_encode(
            PricingPlan::query()->active()->get(['name', 'headline', 'description', 'included'])->toArray(),
            JSON_UNESCAPED_UNICODE
        );

        foreach (['pedidos mensuales', 'límite de pedidos', 'hasta 500 pedidos'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $text);
        }
    }

    // Caso 12 ---------------------------------------------------------------

    public function test_case_12_a_price_change_applies_to_new_quotations_only(): void
    {
        $package = $this->package('p1-esencial-whatsapp');
        $module = $this->module('facturacion-electronica');

        $payload = [
            'business_type_id' => null,
            'plan_id' => $package->id,
            'items' => [['id' => $module->id]],
            'contact' => [
                'name' => 'Ana Pérez',
                'email' => 'ana@burgerhouse.test',
            ],
            'elapsed' => 9000,
        ];

        $first = $this->postJson('/api/quotations', $payload)->assertCreated();
        $firstCode = $first->json('data.code');

        $this->assertEquals(390.0 + 180.0, $first->json('data.setup_total'));

        // El administrador sube la tarifa del módulo.
        $module->forceFill(['setup_price' => 300, 'monthly_price' => 40])->save();

        $payload['contact']['email'] = 'otra@burgerhouse.test';
        $second = $this->postJson('/api/quotations', $payload)->assertCreated();

        $this->assertEquals(390.0 + 300.0, $second->json('data.setup_total'));

        // La propuesta anterior conserva sus importes y su fotografía de tarifas.
        $stored = Quotation::query()->where('code', $firstCode)->sole();

        $this->assertSame(570.0, round((float) $stored->setup_total, 2));
        $this->assertSame(94.0, round((float) $stored->recurring_total, 2));

        $snapshot = collect($stored->pricing_snapshot['items'])->firstWhere('slug', 'facturacion-electronica');

        $this->assertSame(180.0, (float) $snapshot['setup_price']);
    }

    public function test_case_12_every_tariff_change_is_recorded_in_the_history(): void
    {
        $module = $this->module('facturacion-electronica');

        $module->update(['setup_price' => 240]);

        $audit = PricingAudit::query()
            ->where('auditable_type', PricingItem::class)
            ->where('auditable_id', $module->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'Un cambio de precio debe quedar registrado.');
        $this->assertSame('180.00', (string) $audit->changes['setup_price']['from']);
        $this->assertSame(240, $audit->changes['setup_price']['to']);
    }
}
