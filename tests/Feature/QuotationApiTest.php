<?php

namespace Tests\Feature;

use App\Mail\QuotationCreatedMail;
use App\Models\BusinessType;
use App\Models\Lead;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\Quotation;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotationApiTest extends TestCase
{
    use RefreshDatabase;

    private PricingPlan $package;

    private PricingItem $module;

    private PricingItem $branch;

    private BusinessType $type;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->type = BusinessType::query()->create([
            'name' => 'Hamburguesería',
            'slug' => 'hamburgueseria',
            'is_active' => true,
        ]);

        $this->package = PricingPlan::query()->create([
            'name' => 'Operación en local',
            'slug' => 'operacion-en-local',
            'level' => 3,
            'setup_price' => 900,
            'monthly_price' => 60,
            'currency' => 'USD',
            'included_item_slugs' => [],
            'is_active' => true,
        ]);

        $this->module = PricingItem::query()->create([
            'name' => 'Facturación electrónica',
            'slug' => 'facturacion-electronica',
            'group' => 'module',
            'category' => 'integraciones',
            'setup_price' => 150,
            'monthly_price' => 25,
            'currency' => 'USD',
            'is_available' => true,
            'is_active' => true,
        ]);

        $this->branch = PricingItem::query()->create([
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
        ]);

        SiteSetting::query()->create(['key' => 'pricing_published', 'type' => 'boolean', 'value' => '1', 'group' => 'pricing']);
        SiteSetting::query()->create(['key' => 'quotation_validity_days', 'type' => 'string', 'value' => '15', 'group' => 'pricing']);
        SiteSetting::flush();
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'business_type_id' => $this->type->id,
            'plan_id' => $this->package->id,
            'items' => [['id' => $this->module->id]],
            'scalability' => ['branches' => 2],
            'catalog' => ['products' => 40],
            'notes' => 'Tenemos unos 40 productos.',
            'contact' => [
                'name' => 'Ana Pérez',
                'company' => 'Burger House',
                'email' => 'ana@burgerhouse.test',
                'phone' => '+51 999 888 777',
            ],
            'send_email' => false,
            'elapsed' => 9000,
        ], $overrides);
    }

    public function test_calculate_returns_backend_totals_without_persisting(): void
    {
        $response = $this->postJson('/api/quotations/calculate', [
            'plan_id' => $this->package->id,
            'items' => [['id' => $this->module->id]],
            'scalability' => ['branches' => 2],
        ]);

        $response->assertOk();
        $this->assertEquals(1170, $response->json('data.setup_subtotal'));  // 900 + 150 + 120
        $this->assertEquals(110, $response->json('data.monthly_subtotal')); // 60 + 25 + 25
        $this->assertEquals(1280, $response->json('data.first_payment'));
        $this->assertEquals(1170 + (110 * 12), $response->json('data.year_projection'));
        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_prices_sent_by_the_browser_are_ignored(): void
    {
        $response = $this->postJson('/api/quotations/calculate', [
            'plan_id' => $this->package->id,
            'items' => [['id' => $this->module->id, 'setup_price' => 1, 'monthly_price' => 1]],
            'setup_total' => 1,
            'monthly_total' => 1,
            'first_payment' => 1,
        ]);

        $response->assertOk();
        $this->assertEquals(1050, $response->json('data.setup_total'));
        $this->assertEquals(85, $response->json('data.monthly_total'));
    }

    public function test_storing_a_quotation_creates_the_lead_and_the_items(): void
    {
        $response = $this->postJson('/api/quotations', $this->payload());

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => ['code', 'token', 'setup_total', 'monthly_total', 'first_payment', 'year_projection', 'pdf_url', 'items'],
        ]);

        $quotation = Quotation::query()->firstOrFail();

        $this->assertSame(1170.0, round((float) $quotation->setup_total, 2));
        $this->assertSame(110.0, round((float) $quotation->recurring_total, 2));
        $this->assertSame(1280.0, round((float) $quotation->total, 2));
        $this->assertTrue($quotation->is_priced);
        $this->assertSame('draft', $quotation->status);
        $this->assertSame('Hamburguesería', $quotation->business_type_label);
        $this->assertSame(now()->addDays(15)->toDateString(), $quotation->valid_until->toDateString());

        // Paquete + módulo + sucursal adicional.
        $this->assertCount(3, $quotation->items);

        $lead = Lead::query()->firstOrFail();
        $this->assertSame('ana@burgerhouse.test', $lead->email);
        $this->assertSame('quotation', $lead->source);
        $this->assertSame($quotation->lead_id, $lead->id);
    }

    public function test_the_stored_lines_separate_setup_from_monthly(): void
    {
        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $line = Quotation::query()->firstOrFail()
            ->items()
            ->where('pricing_item_id', $this->module->id)
            ->sole();

        $this->assertSame(150.0, round((float) $line->setup_price, 2));
        $this->assertSame(25.0, round((float) $line->monthly_price, 2));
        $this->assertFalse((bool) $line->is_included);
        $this->assertFalse((bool) $line->is_auto_added);
    }

    public function test_a_quotation_keeps_a_snapshot_of_the_tariff_it_used(): void
    {
        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $snapshot = Quotation::query()->firstOrFail()->pricing_snapshot;

        $this->assertArrayHasKey('taken_at', $snapshot);
        $this->assertSame(900.0, (float) collect($snapshot['packages'])->firstWhere('slug', 'operacion-en-local')['setup_price']);
        $this->assertSame(150.0, (float) collect($snapshot['items'])->firstWhere('slug', 'facturacion-electronica')['setup_price']);
        $this->assertSame(1280.0, (float) $snapshot['totals']['first_payment']);
    }

    public function test_the_catalogue_size_is_recorded_but_never_charged(): void
    {
        $withoutCatalog = $this->postJson('/api/quotations/calculate', [
            'plan_id' => $this->package->id,
        ])->json('data.monthly_total');

        $withCatalog = $this->postJson('/api/quotations/calculate', [
            'plan_id' => $this->package->id,
            'catalog' => ['products' => 9000, 'categories' => 400, 'variations' => 8000],
        ])->json('data.monthly_total');

        $this->assertEquals($withoutCatalog, $withCatalog);

        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $this->assertSame(40, Quotation::query()->firstOrFail()->payload['catalog']['products']);
    }

    public function test_quotation_codes_are_sequential(): void
    {
        $this->postJson('/api/quotations', $this->payload())->assertCreated();
        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $codes = Quotation::query()->orderBy('id')->pluck('code');

        $this->assertSame('HF-'.now()->format('Ym').'-0001', $codes[0]);
        $this->assertSame('HF-'.now()->format('Ym').'-0002', $codes[1]);
    }

    public function test_a_returning_contact_reuses_its_lead(): void
    {
        $this->postJson('/api/quotations', $this->payload())->assertCreated();
        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseCount('quotations', 2);
    }

    public function test_the_honeypot_rejects_automated_submissions(): void
    {
        $response = $this->postJson('/api/quotations', $this->payload(['website' => 'http://spam.test']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('website');
        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_submissions_faster_than_a_human_are_rejected(): void
    {
        $response = $this->postJson('/api/quotations', $this->payload(['elapsed' => 120]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('elapsed');
    }

    public function test_contact_details_are_required(): void
    {
        $payload = $this->payload();
        $payload['contact']['email'] = 'no-es-un-correo';
        unset($payload['contact']['name']);

        $response = $this->postJson('/api/quotations', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['contact.name', 'contact.email']);
    }

    public function test_a_package_is_always_required_to_store_a_quotation(): void
    {
        $payload = $this->payload();
        unset($payload['plan_id']);

        $this->postJson('/api/quotations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plan_id');
    }

    public function test_the_quotation_pdf_can_be_downloaded(): void
    {
        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $quotation = Quotation::query()->firstOrFail();

        $response = $this->get(route('quotations.pdf', $quotation->public_token));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_an_unpriced_configuration_is_still_stored(): void
    {
        SiteSetting::query()->where('key', 'pricing_published')->update(['value' => '0']);
        SiteSetting::flush();

        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $quotation = Quotation::query()->firstOrFail();

        $this->assertFalse($quotation->is_priced);
        $this->assertCount(3, $quotation->items);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_the_quotation_can_be_emailed_to_the_contact(): void
    {
        $this->postJson('/api/quotations', $this->payload())->assertCreated();

        $quotation = Quotation::query()->firstOrFail();

        $response = $this->postJson("/api/quotations/{$quotation->public_token}/email", [
            'email' => 'otro@negocio.test',
        ]);

        $response->assertOk();
        Mail::assertSent(QuotationCreatedMail::class);
        $this->assertNotNull($quotation->fresh()->emailed_at);
    }
}
