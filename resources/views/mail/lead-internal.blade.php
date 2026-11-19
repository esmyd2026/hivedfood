<x-mail::message>
# Nuevo contacto desde la web

**Nombre:** {{ $lead->name }}
**Correo:** {{ $lead->email }}
@if ($lead->phone)
**Teléfono:** {{ $lead->phone }}
@endif
@if ($lead->company)
**Negocio:** {{ $lead->company }}
@endif
@if ($lead->business_type_label || $lead->businessType)
**Tipo de negocio:** {{ $lead->businessType?->name ?? $lead->business_type_label }}
@endif
**Origen:** {{ $lead->source }}

@if ($lead->message)
## Mensaje
{{ $lead->message }}
@endif

<x-mail::button :url="url('/admin/leads')">
Abrir en el panel
</x-mail::button>
</x-mail::message>
