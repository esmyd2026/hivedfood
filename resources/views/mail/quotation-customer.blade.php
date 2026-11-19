<x-mail::message>
# Tu cotización está lista

Hola {{ $quotation->lead?->name }}, gracias por configurar tu solución en HIVEDFOOD.

**Referencia:** {{ $quotation->code }}
**Tipo de negocio:** {{ $quotation->business_type_label ?? '—' }}
**Paquete:** {{ $quotation->plan_label ?? '—' }}

@if ($quotation->is_priced)
<x-mail::table>
| Concepto | Total |
|:---------|------:|
| Implementación | {{ number_format((float) $quotation->setup_total, 2) }} {{ $quotation->currency }} |
| Servicio mensual fijo | {{ number_format((float) $quotation->recurring_total, 2) }} {{ $quotation->currency }} |
@if ($quotation->usage_monthly !== null)
| Consumo estimado por pedidos | {{ number_format((float) $quotation->usage_monthly, 2) }} {{ $quotation->currency }} |
@endif
| **Primer pago** | **{{ number_format((float) $quotation->total, 2) }} {{ $quotation->currency }}** |
</x-mail::table>

El primer pago reúne la implementación y la primera mensualidad. El consumo de Meta y del asistente con IA se factura por separado, según el uso real.
@else
Nuestro equipo comercial revisará tu configuración y te enviará la propuesta con los precios finales.
@endif

Adjuntamos el detalle completo en PDF.

@if ($quotation->valid_until)
Esta cotización es válida hasta el {{ $quotation->valid_until->format('d/m/Y') }}.
@endif

Gracias,<br>
Equipo HIVEDFOOD
</x-mail::message>
