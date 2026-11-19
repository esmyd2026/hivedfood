<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateQuotationRequest;
use App\Http\Requests\StoreQuotationRequest;
use App\Mail\QuotationCreatedMail;
use App\Mail\QuotationInternalMail;
use App\Models\Quotation;
use App\Models\SiteSetting;
use App\Services\QuotationCalculator;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class QuotationController extends Controller
{
    public function __construct(
        private QuotationCalculator $calculator,
        private QuotationService $quotations,
    ) {}

    /** Live preview of the configuration. Nothing is persisted here. */
    public function calculate(CalculateQuotationRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->calculator->calculate($request->configuration()),
        ]);
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $configuration = $request->configuration();
        $configuration['business_type_note'] = $request->input('business_type_note');

        $quotation = $this->quotations->create(
            $configuration,
            $request->validated('contact'),
            ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        $this->notify($quotation, (bool) $request->boolean('send_email'));

        return response()->json([
            'message' => 'Tu cotización fue registrada correctamente.',
            'data' => $this->present($quotation),
        ], HttpResponse::HTTP_CREATED);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        return response()->json(['data' => $this->present($quotation)]);
    }

    public function pdf(Quotation $quotation)
    {
        return $this->quotations
            ->pdf($quotation)
            ->download($this->quotations->pdfFilename($quotation));
    }

    public function email(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email:rfc', 'max:190'],
        ]);

        $to = $validated['email'] ?? $quotation->lead?->email;

        if (blank($to)) {
            return response()->json([
                'message' => 'No tenemos un correo al cual enviar la cotización.',
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            Mail::to($to)->send(new QuotationCreatedMail($quotation));
            $quotation->forceFill(['emailed_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar la cotización por correo.', [
                'quotation' => $quotation->code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No pudimos enviar el correo en este momento. Puedes descargar el PDF.',
            ], HttpResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json(['message' => 'Cotización enviada a '.$to]);
    }

    private function notify(Quotation $quotation, bool $sendToCustomer): void
    {
        $internal = SiteSetting::get('notification_email');

        try {
            if ($sendToCustomer && filled($quotation->lead?->email)) {
                Mail::to($quotation->lead->email)->send(new QuotationCreatedMail($quotation));
                $quotation->forceFill(['emailed_at' => now()])->save();
            }

            if (filled($internal)) {
                Mail::to($internal)->send(new QuotationInternalMail($quotation));
            }
        } catch (\Throwable $e) {
            // A mail failure must never lose the commercial record.
            Log::error('Fallo al notificar la cotización.', [
                'quotation' => $quotation->code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function present(Quotation $quotation): array
    {
        $quotation->loadMissing('items');

        $payload = $quotation->payload ?? [];

        return [
            'code' => $quotation->code,
            'token' => $quotation->public_token,
            'currency' => $quotation->currency,
            'is_priced' => $quotation->is_priced,
            'setup_subtotal' => (float) $quotation->setup_subtotal,
            'monthly_subtotal' => (float) $quotation->recurring_subtotal,
            'setup_discount' => (float) $quotation->setup_discount,
            'monthly_discount' => (float) $quotation->recurring_discount,
            'setup_total' => (float) $quotation->setup_total,
            'monthly_total' => (float) $quotation->recurring_total,
            'usage_monthly' => $quotation->usage_monthly === null ? null : (float) $quotation->usage_monthly,
            'monthly_estimated_total' => (float) ($payload['monthly_estimated_total'] ?? $quotation->recurring_total),
            'billing_model' => $quotation->billing_model_label,
            'usage' => $payload['usage'] ?? null,
            // Implementacion mas la primera mensualidad.
            'first_payment' => (float) $quotation->total,
            'year_projection' => (float) ($payload['year_projection'] ?? 0),
            'projection_months' => (int) ($payload['projection_months'] ?? 12),
            'discounts' => $quotation->discounts ?? [],
            'conditions' => $quotation->conditions ?? [],
            'external_costs' => $payload['external_costs'] ?? [],
            'dependencies' => $payload['dependencies'] ?? ['auto_added' => [], 'notes' => []],
            'scalability' => $payload['scalability'] ?? [],
            'valid_until' => $quotation->valid_until?->toDateString(),
            'business_type' => $quotation->business_type_label,
            'plan' => $quotation->plan_label,
            'items' => $quotation->items->map(fn ($item) => [
                'name' => $item->name,
                'group' => $item->group,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'setup_price' => $item->setup_price !== null ? (float) $item->setup_price : null,
                'monthly_price' => $item->monthly_price !== null ? (float) $item->monthly_price : null,
                'is_included' => (bool) $item->is_included,
                'is_auto_added' => (bool) $item->is_auto_added,
                'is_priced' => (bool) $item->is_priced,
            ])->values()->all(),
            'pdf_url' => route('quotations.pdf', $quotation->public_token),
        ];
    }
}
