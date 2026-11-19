<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::query()->with('businessType:id,name')->withCount('quotations');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'source'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->query($filter));
            }
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate(min(100, max(5, (int) $request->query('per_page', 20))))
        );
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json([
            'data' => $lead->load(['businessType:id,name', 'quotations:id,lead_id,code,status,total,currency,is_priced,created_at']),
        ]);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['sometimes', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(Lead::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
        ]);

        if (($validated['status'] ?? null) === 'contacted' && $lead->contacted_at === null) {
            $validated['contacted_at'] = now();
        }

        $lead->update($validated);

        return response()->json(['message' => 'Lead actualizado.', 'data' => $lead->fresh()]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(['message' => 'Lead eliminado.']);
    }
}
