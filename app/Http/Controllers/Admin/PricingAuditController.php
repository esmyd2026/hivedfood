<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Historial de cambios del modelo comercial.
 *
 * Solo lectura: el registro se escribe solo desde los modelos auditados y
 * nunca debe poder editarse desde el panel.
 */
class PricingAuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PricingAudit::query()->with('user:id,name');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('label', 'like', "%{$search}%");
        }

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        $page = $query
            ->orderByDesc('id')
            ->paginate(min(100, max(5, (int) $request->query('per_page', 25))));

        $page->getCollection()->transform(fn (PricingAudit $audit) => [
            'id' => $audit->id,
            'label' => $audit->label,
            'action' => $audit->action,
            'type' => class_basename($audit->auditable_type),
            'changes' => $audit->changes ?? [],
            'user' => $audit->user?->name,
            'created_at' => $audit->created_at?->toIso8601String(),
        ]);

        return response()->json($page);
    }
}
