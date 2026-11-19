<?php

namespace App\Services;

use App\Models\BillingModel;
use App\Models\Lead;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\Quotation;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuotationService
{
    public function __construct(private QuotationCalculator $calculator) {}

    /**
     * Persist a quotation together with its lead.
     *
     * El cálculo se rehace siempre en el servidor a partir de la
     * configuración enviada, y se guarda una fotografía del tarifario usado
     * para que una subida de precios no altere propuestas ya emitidas.
     *
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $contact
     */
    public function create(array $configuration, array $contact, array $context = []): Quotation
    {
        $result = $this->calculator->calculate($configuration);

        return DB::transaction(function () use ($result, $configuration, $contact, $context) {
            $lead = $this->upsertLead($contact, $configuration, $context);

            $quotation = Quotation::query()->create([
                'code' => $this->nextCode(),
                'public_token' => (string) Str::uuid(),
                'lead_id' => $lead->id,
                'business_type_id' => $result['business_type']['id'] ?? null,
                'pricing_plan_id' => $result['plan']['id'] ?? null,
                'billing_model_id' => $result['billing_model']['id'] ?? null,
                'billing_model_label' => $result['billing_model']['name'] ?? null,
                'business_type_label' => $result['business_type']['name']
                    ?? ($configuration['business_type_note'] ?? null),
                'plan_label' => $result['plan']['name'] ?? null,
                'currency' => $result['currency'],
                'setup_subtotal' => $result['setup_subtotal'],
                'recurring_subtotal' => $result['monthly_subtotal'],
                'setup_discount' => $result['setup_discount'],
                'recurring_discount' => $result['monthly_discount'],
                'setup_total' => $result['setup_total'],
                'recurring_total' => $result['monthly_total'],
                'usage_monthly' => $result['usage_monthly'],
                'total' => $result['first_payment'],
                'is_priced' => $result['is_priced'],
                'status' => 'draft',
                'discounts' => $result['discounts'],
                'conditions' => $result['conditions'],
                'payload' => [
                    'configuration' => $configuration,
                    'business_type_note' => $configuration['business_type_note'] ?? null,
                    'scalability' => $result['scalability'],
                    'usage' => $result['usage'],
                    'dependencies' => $result['dependencies'],
                    'catalog' => $configuration['catalog'] ?? [],
                    'first_payment' => $result['first_payment'],
                    'monthly_estimated_total' => $result['monthly_estimated_total'],
                    'year_projection' => $result['year_projection'],
                    'projection_months' => $result['projection_months'],
                    'external_costs' => $result['external_costs'],
                    'unpriced_count' => $result['unpriced_count'],
                ],
                'pricing_snapshot' => $this->snapshot($result),
                'notes' => $configuration['notes'] ?? null,
                'valid_until' => now()->addDays((int) $result['validity_days'])->toDateString(),
                'ip_address' => $context['ip'] ?? null,
            ]);

            foreach ($result['lines'] as $line) {
                $quotation->items()->create([
                    'pricing_item_id' => $line['pricing_item_id'],
                    'name' => $line['name'],
                    'group' => $line['group'],
                    'description' => $line['description'],
                    'setup_price' => $line['setup_price'],
                    'monthly_price' => $line['monthly_price'],
                    'quantity' => $line['quantity'],
                    'is_priced' => $line['is_priced'],
                    'is_included' => $line['is_included'],
                    'is_auto_added' => $line['is_auto_added'],
                    'sort_order' => $line['sort_order'],
                ]);
            }

            return $quotation->load(['items', 'lead', 'businessType', 'plan']);
        });
    }

    /**
     * Fotografía del tarifario en el momento de emitir.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function snapshot(array $result): array
    {
        return [
            'taken_at' => now()->toIso8601String(),
            'currency' => $result['currency'],
            'package' => $result['plan'],
            'packages' => PricingPlan::query()
                ->active()
                ->ordered()
                ->get(['slug', 'name', 'level', 'setup_price', 'monthly_price'])
                ->toArray(),
            'items' => PricingItem::query()
                ->active()
                ->ordered()
                ->get(['slug', 'name', 'group', 'unit_kind', 'setup_price', 'monthly_price', 'included_quantity'])
                ->toArray(),
            'billing_models' => BillingModel::query()
                ->active()
                ->ordered()
                ->with('activeTiers:id,billing_model_id,label,from_orders,to_orders,price_per_order')
                ->get(['id', 'slug', 'name', 'charges_monthly', 'charges_usage', 'included_orders', 'volume_basis', 'min_monthly'])
                ->toArray(),
            'totals' => [
                'setup_total' => $result['setup_total'],
                'monthly_total' => $result['monthly_total'],
                'usage_monthly' => $result['usage_monthly'],
                'first_payment' => $result['first_payment'],
                'year_projection' => $result['year_projection'],
            ],
        ];
    }

    /**
     * Copia una cotización para renegociarla sin tocar la original.
     *
     * Se recalcula con el tarifario vigente: duplicar sirve para actualizar
     * una propuesta, no para arrastrar precios antiguos sin saberlo.
     */
    public function duplicate(Quotation $source): Quotation
    {
        $configuration = $source->payload['configuration'] ?? [];
        $lead = $source->lead;

        $contact = [
            'name' => $lead?->name ?? 'Sin contacto',
            'company' => $lead?->company,
            'email' => $lead?->email ?? 'sin-correo@hivedfood.test',
            'phone' => $lead?->phone,
            'city' => $lead?->city,
        ];

        $copy = $this->create($configuration, $contact, ['ip' => null]);
        $copy->update([
            'duplicated_from_id' => $source->id,
            'internal_notes' => sprintf('Duplicada de %s.', $source->code),
        ]);

        return $copy->fresh(['items', 'lead']);
    }

    /**
     * @param  array<string, mixed>  $contact
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $context
     */
    private function upsertLead(array $contact, array $configuration, array $context): Lead
    {
        $email = strtolower(trim((string) $contact['email']));

        $lead = Lead::query()->where('email', $email)->latest('id')->first();

        $attributes = [
            'name' => $contact['name'],
            'company' => $contact['company'] ?? null,
            'email' => $email,
            'phone' => $contact['phone'] ?? null,
            'city' => $contact['city'] ?? null,
            'business_type_id' => $configuration['business_type_id'] ?? null,
            'business_type_label' => $configuration['business_type_note'] ?? null,
            'source' => 'quotation',
            'accepts_marketing' => (bool) ($contact['accepts_marketing'] ?? false),
            'ip_address' => $context['ip'] ?? null,
            'user_agent' => Str::limit((string) ($context['user_agent'] ?? ''), 240, ''),
        ];

        if ($lead) {
            // Keep the commercial status already assigned by the sales team.
            $lead->fill(array_filter($attributes, fn ($value) => $value !== null && $value !== ''));
            $lead->save();

            return $lead;
        }

        return Lead::query()->create($attributes + ['status' => 'new']);
    }

    private function nextCode(): string
    {
        $prefix = 'HF-'.now()->format('Ym');

        $last = Quotation::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $sequence = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $sequence);
    }

    /** Render the quotation as a PDF document. */
    public function pdf(Quotation $quotation): \Barryvdh\DomPDF\PDF
    {
        $quotation->loadMissing(['items', 'lead', 'businessType', 'plan']);

        return Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'settings' => SiteSetting::all_values(),
        ])->setPaper('a4');
    }

    public function pdfFilename(Quotation $quotation): string
    {
        return 'cotizacion-'.strtolower($quotation->code).'.pdf';
    }

    /** Enlace de WhatsApp para compartir la propuesta con el cliente. */
    public function whatsappShareUrl(Quotation $quotation): ?string
    {
        $number = preg_replace('/\D+/', '', (string) ($quotation->lead?->phone ?? ''));

        if (blank($number)) {
            return null;
        }

        $text = sprintf(
            'Hola %s, aquí tienes tu cotización %s de %s: %s',
            $quotation->lead?->name ?? '',
            $quotation->code,
            SiteSetting::get('site_name', 'HIVEDFOOD'),
            route('quotations.pdf', $quotation->public_token),
        );

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }
}
