<x-mail::message>
# Nueva cotización generada

**Referencia:** {{ $quotation->code }}
**Contacto:** {{ $quotation->lead?->name }} ({{ $quotation->lead?->email }})
@if ($quotation->lead?->phone)
**Teléfono:** {{ $quotation->lead->phone }}
@endif
@if ($quotation->lead?->company)
**Negocio:** {{ $quotation->lead->company }}
@endif
**Tipo de negocio:** {{ $quotation->business_type_label ?? '—' }}
**Paquete:** {{ $quotation->plan_label ?? '—' }}
**Forma de cobro:** {{ $quotation->billing_model_label ?? '—' }}
**Precios publicados:** {{ $quotation->is_priced ? 'sí' : 'no' }}
@if ($quotation->is_priced)
**Implementación:** {{ number_format((float) $quotation->setup_total, 2) }} {{ $quotation->currency }}
**Servicio mensual:** {{ number_format((float) $quotation->recurring_total, 2) }} {{ $quotation->currency }}
**Primer pago:** {{ number_format((float) $quotation->total, 2) }} {{ $quotation->currency }}
@if ($quotation->usage_monthly !== null)
**Consumo estimado por pedidos:** {{ number_format((float) $quotation->usage_monthly, 2) }} {{ $quotation->currency }} al mes
@endif
@endif

## Conceptos cotizados
@foreach ($quotation->items->where('is_included', false) as $item)
- {{ $item->name }} (×{{ $item->quantity }}) — implementación {{ $item->setup_price === null ? 'a cotizar' : number_format((float) $item->setup_price, 2) }} · mensual {{ $item->monthly_price === null ? 'a cotizar' : number_format((float) $item->monthly_price, 2) }}
@endforeach

## Incluido en el paquete
@foreach ($quotation->items->where('is_included', true) as $item)
- {{ $item->name }}
@endforeach

@if ($quotation->notes)
## Notas del cliente
{{ $quotation->notes }}
@endif

<x-mail::button :url="url('/admin/cotizaciones')">
Abrir en el panel
</x-mail::button>
</x-mail::message>
