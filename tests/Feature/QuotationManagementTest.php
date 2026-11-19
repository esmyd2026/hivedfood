<?php

namespace Tests\Feature;

use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\Quotation;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\QuotationService;
use Database\Seeders\BillingModelSeeder;
use Database\Seeders\PricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Gestión comercial de las cotizaciones desde el panel.
 */
class QuotationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seed(PricingSeeder::class);
        $this->seed(BillingModelSeeder::class);

        SiteSetting::query()->updateOrCreate(
            ['key' => 'pricing_published'],
            ['type' => 'boolean', 'value' => '1', 'group' => 'pricing']
        );
        SiteSetting::flush();
    }

    private function admin(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'admin@hivedfood.test'],
            ['name' => 'Admin', 'password' => Hash::make('secret-password'), 'is_admin' => true]
        );
    }

    private function quotation(): Quotation
    {
        return app(QuotationService::class)->create(
            [
                'plan_id' => PricingPlan::query()->where('slug', 'p1-esencial-whatsapp')->value('id'),
                'items' => [['id' => PricingItem::query()->where('slug', 'facturacion-electronica')->value('id')]],
                'scalability' => ['branches' => 2],
            ],
            [
                'name' => 'Ana Pérez',
                'company' => 'Burger House',
                'email' => 'ana@burgerhouse.test',
                'phone' => '+51 999 888 777',
            ],
        );
    }

    public function test_a_new_quotation_starts_as_a_draft(): void
    {
        $this->assertSame('draft', $this->quotation()->status);
    }

    public function test_the_detail_exposes_the_share_links(): void
    {
        $quotation = $this->quotation();

        $response = $this->actingAs($this->admin())
            ->getJson("/admin/api/cotizaciones/{$quotation->id}")
            ->assertOk();

        $this->assertStringContainsString('wa.me/51999888777', $response->json('meta.whatsapp_url'));
        $this->assertStringContainsString($quotation->public_token, $response->json('meta.pdf_url'));
    }

    public function test_duplicating_recalculates_with_the_current_tariff(): void
    {
        $original = $this->quotation();

        // El tarifario sube antes de renegociar.
        PricingItem::query()->where('slug', 'facturacion-electronica')->update(['setup_price' => 400]);

        $response = $this->actingAs($this->admin())
            ->postJson("/admin/api/cotizaciones/{$original->id}/duplicar")
            ->assertCreated();

        $copy = Quotation::query()->findOrFail($response->json('data.id'));

        $this->assertSame($original->id, $copy->duplicated_from_id);
        $this->assertNotSame($original->code, $copy->code);
        // 390 del paquete + 400 del módulo + 120 de la segunda sucursal.
        $this->assertSame(910.0, round((float) $copy->setup_total, 2));
        // La original no se toca: conserva los 180 que costaba el módulo.
        $this->assertSame(690.0, round((float) $original->fresh()->setup_total, 2));
    }

    public function test_only_an_accepted_quotation_becomes_an_implementation_request(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->postJson("/admin/api/cotizaciones/{$quotation->id}/implementacion")
            ->assertUnprocessable();

        $quotation->forceFill(['status' => 'accepted'])->save();

        $this->actingAs($this->admin())
            ->postJson("/admin/api/cotizaciones/{$quotation->id}/implementacion")
            ->assertOk();

        $this->assertNotNull($quotation->fresh()->implementation_requested_at);

        // No se puede solicitar dos veces.
        $this->actingAs($this->admin())
            ->postJson("/admin/api/cotizaciones/{$quotation->id}/implementacion")
            ->assertConflict();
    }

    public function test_the_commercial_statuses_are_the_ones_the_sales_team_uses(): void
    {
        $quotation = $this->quotation();

        foreach (['sent', 'negotiating', 'accepted', 'rejected', 'expired'] as $status) {
            $this->actingAs($this->admin())
                ->postJson("/admin/api/cotizaciones/{$quotation->id}", ['status' => $status])
                ->assertOk();

            $this->assertSame($status, $quotation->fresh()->status);
        }

        $this->actingAs($this->admin())
            ->postJson("/admin/api/cotizaciones/{$quotation->id}", ['status' => 'inventado'])
            ->assertUnprocessable();
    }

    public function test_the_pricing_history_is_readable_from_the_panel(): void
    {
        PricingItem::query()->where('slug', 'inventario-stock')->first()->update(['monthly_price' => 33]);

        $rows = $this->actingAs($this->admin())
            ->getJson('/admin/api/historial-precios')
            ->assertOk()
            ->json('data');

        $entry = collect($rows)->firstWhere('label', 'Inventario y stock');

        $this->assertNotNull($entry);
        $this->assertSame('updated', $entry['action']);
        $this->assertSame(33, $entry['changes']['monthly_price']['to']);
    }

    public function test_the_history_is_read_only(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/api/historial-precios', ['label' => 'manipulado'])
            ->assertStatus(405);
    }
}
