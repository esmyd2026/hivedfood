<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Mail\LeadInternalMail;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $data = $request->validated();

        $lead = Lead::query()->create([
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'business_type_id' => $data['business_type_id'] ?? null,
            'business_type_label' => $data['business_type_label'] ?? null,
            'source' => $data['source'] ?? 'contact_form',
            'status' => 'new',
            'message' => $data['message'] ?? null,
            'accepts_marketing' => (bool) ($data['accepts_marketing'] ?? false),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 240, ''),
            'meta' => array_filter([
                'referer' => $request->headers->get('referer'),
                'page' => $request->input('page'),
            ]),
        ]);

        if (filled($data['quotation_token'] ?? null)) {
            Quotation::query()
                ->where('public_token', $data['quotation_token'])
                ->whereNull('lead_id')
                ->update(['lead_id' => $lead->id]);
        }

        $this->notify($lead);

        return response()->json([
            'message' => 'Gracias. Hemos recibido tu solicitud y te contactaremos pronto.',
            'data' => ['id' => $lead->id],
        ], HttpResponse::HTTP_CREATED);
    }

    private function notify(Lead $lead): void
    {
        $internal = SiteSetting::get('notification_email');

        if (blank($internal)) {
            return;
        }

        try {
            Mail::to($internal)->send(new LeadInternalMail($lead));
        } catch (\Throwable $e) {
            Log::error('Fallo al notificar un lead.', ['lead' => $lead->id, 'error' => $e->getMessage()]);
        }
    }
}
