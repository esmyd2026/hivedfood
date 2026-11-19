<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\QuotationCreatedMail;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class QuotationController extends Controller
{
    public function __construct(private QuotationService $quotations) {}

    public function index(Request $request): JsonResponse
    {
        $query = Quotation::query()->with(['lead:id,name,email,company', 'plan:id,name']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn ($l) => $l->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate(min(100, max(5, (int) $request->query('per_page', 20))))
        );
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $quotation->load(['items', 'lead', 'businessType:id,name', 'plan:id,name', 'duplicatedFrom:id,code']);

        return response()->json([
            'data' => $quotation,
            'meta' => [
                'whatsapp_url' => $this->quotations->whatsappShareUrl($quotation),
                'pdf_url' => route('quotations.pdf', $quotation->public_token),
            ],
        ]);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(Quotation::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'valid_until' => ['nullable', 'date'],
        ]);

        $quotation->update($validated);

        return response()->json(['message' => 'Cotización actualizada.', 'data' => $quotation->fresh()]);
    }

    /**
     * Copia la cotización para renegociarla.
     *
     * La copia se recalcula con el tarifario vigente; la original conserva
     * intactos sus importes y su fotografía de precios.
     */
    public function duplicate(Quotation $quotation): JsonResponse
    {
        $copy = $this->quotations->duplicate($quotation);

        return response()->json([
            'message' => 'Cotización duplicada como '.$copy->code.'.',
            'data' => $copy,
        ], HttpResponse::HTTP_CREATED);
    }

    /** Convierte una propuesta aceptada en una solicitud de implementación. */
    public function requestImplementation(Quotation $quotation): JsonResponse
    {
        if ($quotation->status !== 'accepted') {
            return response()->json([
                'message' => 'Solo las cotizaciones aceptadas pasan a implementación.',
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($quotation->implementation_requested_at) {
            return response()->json([
                'message' => 'Esta cotización ya tiene una solicitud de implementación.',
            ], HttpResponse::HTTP_CONFLICT);
        }

        $quotation->forceFill(['implementation_requested_at' => now()])->save();

        return response()->json([
            'message' => 'Solicitud de implementación registrada.',
            'data' => $quotation->fresh(),
        ]);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $quotation->delete();

        return response()->json(['message' => 'Cotización eliminada.']);
    }

    public function pdf(Quotation $quotation)
    {
        return $this->quotations->pdf($quotation)->download($this->quotations->pdfFilename($quotation));
    }

    public function resend(Request $request, Quotation $quotation): JsonResponse
    {
        $to = $request->validate(['email' => ['nullable', 'email:rfc']])['email']
            ?? $quotation->lead?->email;

        if (blank($to)) {
            return response()->json(['message' => 'La cotización no tiene un correo asociado.'], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            Mail::to($to)->send(new QuotationCreatedMail($quotation));
            $quotation->forceFill(['emailed_at' => now(), 'status' => 'sent'])->save();
        } catch (\Throwable $e) {
            Log::error('No se pudo reenviar la cotización.', ['quotation' => $quotation->code, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'No se pudo enviar el correo: '.$e->getMessage()], HttpResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json(['message' => 'Cotización enviada a '.$to]);
    }
}
