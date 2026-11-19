@php
    $currency = $settings['pricing_currency_symbol'] ?? '$';
    $money = fn ($value) => $value === null ? 'A cotizar' : $currency.number_format((float) $value, 2);
    $payload = $quotation->payload ?? [];
    $scalability = $payload['scalability'] ?? [];
    $usage = $payload['usage'] ?? null;
    $dependencies = $payload['dependencies'] ?? [];
    $externalCosts = $payload['external_costs'] ?? [];
    $months = (int) ($payload['projection_months'] ?? 12);

    $included = $quotation->items->where('is_included', true);
    $charged = $quotation->items->where('is_included', false);
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quotation->code }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #202A24; line-height: 1.5; }
        .brand { color: #202A24; font-size: 20px; font-weight: bold; letter-spacing: -0.5px; }
        .brand span { color: #E96D42; }
        .muted { color: #6a7b72; }
        .rule { border-bottom: 2px solid #F4B83F; margin: 10px 0 18px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .items th { background: #F7F5EF; text-align: left; padding: 7px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .8px; border-bottom: 1px solid #e2ded2; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #efece3; }
        .items td.num, .items th.num { text-align: right; white-space: nowrap; }
        .group { margin-top: 16px; font-size: 11px; font-weight: bold; color: #202A24; }
        .tag { display: inline-block; background: #eef3ef; color: #46605a; padding: 1px 6px; border-radius: 8px; font-size: 8px; margin-left: 4px; }
        .totals { margin-top: 18px; width: 62%; margin-left: 38%; }
        .totals td { padding: 4px 0; }
        .totals td.num { text-align: right; white-space: nowrap; }
        .totals tr.grand td { border-top: 2px solid #202A24; padding-top: 8px; font-size: 13px; font-weight: bold; }
        .note { background: #F7F5EF; border-left: 3px solid #F4B83F; padding: 10px 12px; margin-top: 16px; }
        .pill { display: inline-block; background: #202A24; color: #F4B83F; padding: 3px 9px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .foot { margin-top: 26px; padding-top: 10px; border-top: 1px solid #e6e2d6; font-size: 8.5px; color: #8a9790; }
        h2 { font-size: 12px; margin: 18px 0 6px; }
        ul { margin: 6px 0 0 16px; padding: 0; }
        li { margin-bottom: 3px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td>
                <div class="brand">HIVED<span>FOOD</span></div>
                <div class="muted">{{ $settings['tagline'] ?? '' }}</div>
            </td>
            <td style="text-align: right;">
                <span class="pill">Cotización {{ $quotation->code }}</span>
                <div class="muted" style="margin-top: 6px;">
                    Emitida: {{ $quotation->created_at->format('d/m/Y') }}<br>
                    @if ($quotation->valid_until)
                        Válida hasta: {{ $quotation->valid_until->format('d/m/Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <strong>Cliente</strong><br>
                {{ $quotation->lead?->name ?? '—' }}<br>
                @if ($quotation->lead?->company)
                    {{ $quotation->lead->company }}<br>
                @endif
                <span class="muted">
                    {{ $quotation->lead?->email }}
                    @if ($quotation->lead?->phone) · {{ $quotation->lead->phone }} @endif
                </span>
            </td>
            <td style="width: 50%;">
                <strong>Configuración</strong><br>
                Tipo de negocio: {{ $quotation->business_type_label ?? '—' }}<br>
                @if ($quotation->plan_label)
                    Paquete: {{ $quotation->plan_label }}<br>
                @endif
                @if ($quotation->billing_model_label)
                    Forma de cobro: {{ $quotation->billing_model_label }}<br>
                @endif
                @foreach ($scalability as $unit)
                    {{ $unit['name'] }}: {{ $unit['total'] }}
                    @if (($unit['included'] ?? 0) > 0)
                        <span class="muted">({{ $unit['included'] }} incluida(s))</span>
                    @endif
                    <br>
                @endforeach
            </td>
        </tr>
    </table>

    @if ($charged->isNotEmpty())
        <div class="group">Detalle de la inversión</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="num">Cant.</th>
                    <th class="num">Implementación</th>
                    <th class="num">Mensualidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($charged as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if ($item->is_auto_added)
                                <span class="tag">añadido por dependencia</span>
                            @endif
                            @if ($item->description)
                                <br><span class="muted">{{ $item->description }}</span>
                            @endif
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ $quotation->is_priced || $item->is_priced ? $money($item->setup_price) : '—' }}</td>
                        <td class="num">{{ $quotation->is_priced || $item->is_priced ? $money($item->monthly_price) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($included->isNotEmpty())
        <div class="group">Ya incluido en tu paquete, sin costo adicional</div>
        <table class="items">
            <tbody>
                @foreach ($included as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if ($item->description)
                                <br><span class="muted">{{ $item->description }}</span>
                            @endif
                        </td>
                        <td class="num">Incluido</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($dependencies['auto_added']))
        <div class="note">
            <strong>Módulos activados automáticamente</strong>
            <ul>
                @foreach ($dependencies['auto_added'] as $dependency)
                    <li>{{ $dependency['reason'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($usage)
        <div class="group">Consumo por pedidos (variable, se factura al cierre de cada mes)</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Tramo</th>
                    <th class="num">Pedidos</th>
                    <th class="num">Precio por pedido</th>
                    <th class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usage['tiers'] as $tier)
                    <tr>
                        <td>{{ $tier['label'] }}</td>
                        <td class="num">{{ number_format($tier['orders'], 0, ',', '.') }}</td>
                        <td class="num">{{ $currency.number_format($tier['price_per_order'], 2) }}</td>
                        <td class="num">{{ $money($tier['amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">Las tarifas por pedido se confirman con el equipo comercial.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="note">
            Estimado sobre {{ number_format($usage['total_orders'], 0, ',', '.') }} pedidos al mes
            @if ($usage['branches'] > 1)
                ({{ number_format($usage['orders_per_branch'], 0, ',', '.') }} por cada uno de los
                {{ $usage['branches'] }} locales)
            @endif
            @if ($usage['included_orders'] > 0)
                · {{ number_format($usage['included_orders'], 0, ',', '.') }} pedidos incluidos sin cargo
            @endif
            @if ($usage['minimum_applied'])
                · se aplica el mínimo mensual de {{ $money($usage['min_monthly']) }}
            @endif
            <br>
            <strong>Cada pedido paga el precio del tramo en el que cae</strong>: superar un tramo abarata
            únicamente los pedidos siguientes, nunca los anteriores.
        </div>
    @endif

    @if ($quotation->is_priced || (float) $quotation->setup_total > 0 || (float) $quotation->recurring_total > 0)
        <table class="totals">
            <tr>
                <td>Subtotal implementación</td>
                <td class="num">{{ $money($quotation->setup_subtotal) }}</td>
            </tr>
            <tr>
                <td>Subtotal mensual</td>
                <td class="num">{{ $money($quotation->recurring_subtotal) }}</td>
            </tr>
            @foreach ($quotation->discounts ?? [] as $discount)
                <tr>
                    <td>{{ $discount['name'] }}</td>
                    <td class="num">−{{ $money(($discount['setup_amount'] ?? 0) + ($discount['monthly_amount'] ?? 0)) }}</td>
                </tr>
            @endforeach
            <tr>
                <td>Inversión inicial</td>
                <td class="num">{{ $money($quotation->setup_total) }}</td>
            </tr>
            <tr>
                <td>Servicio mensual fijo</td>
                <td class="num">{{ $money($quotation->recurring_total) }}</td>
            </tr>
            @if ($quotation->usage_monthly !== null)
                <tr>
                    <td>Consumo estimado por pedidos</td>
                    <td class="num">{{ $money($quotation->usage_monthly) }}</td>
                </tr>
                <tr>
                    <td>Mensual estimado con consumo</td>
                    <td class="num">{{ $money((float) $quotation->recurring_total + (float) $quotation->usage_monthly) }}</td>
                </tr>
            @endif
            <tr class="grand">
                <td>Primer pago</td>
                <td class="num">{{ $money($quotation->total) }}</td>
            </tr>
            <tr>
                <td class="muted">Implementación más la primera mensualidad</td>
                <td class="num muted">&nbsp;</td>
            </tr>
            @if (! empty($payload['year_projection']))
                <tr>
                    <td class="muted">Proyección de {{ $months }} meses</td>
                    <td class="num muted">{{ $money($payload['year_projection']) }}</td>
                </tr>
            @endif
        </table>
    @endif

    @if (! $quotation->is_priced)
        <div class="note">
            <strong>Propuesta a medida.</strong><br>
            {{ $settings['pricing_unpriced_message'] ?? 'Nuestro equipo comercial preparará una propuesta con los precios finales para esta configuración.' }}
        </div>
    @endif

    @if (! empty($externalCosts))
        <h2>Servicios externos no incluidos</h2>
        <ul>
            @foreach ($externalCosts as $cost)
                <li><strong>{{ $cost['title'] }}</strong> — {{ $cost['description'] }}</li>
            @endforeach
        </ul>
    @endif

    @if ($quotation->notes)
        <h2>Notas del cliente</h2>
        <div>{{ $quotation->notes }}</div>
    @endif

    @if (!empty($quotation->conditions))
        <h2>Condiciones comerciales</h2>
        <ul>
            @foreach ($quotation->conditions as $condition)
                <li>{{ $condition }}</li>
            @endforeach
        </ul>
    @endif

    <div class="foot">
        {{ ($settings['legal_name'] ?? null) ?: ($settings['site_name'] ?? 'HIVEDFOOD') }}
        @if ($settings['email'] ?? null) · {{ $settings['email'] }} @endif
        @if ($settings['phone'] ?? null) · {{ $settings['phone'] }} @endif
        <br>
        Documento generado automáticamente el {{ now()->format('d/m/Y H:i') }}. Referencia {{ $quotation->code }}.
    </div>
</body>
</html>
