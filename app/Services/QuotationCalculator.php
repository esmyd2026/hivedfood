<?php

namespace App\Services;

use App\Models\BillingModel;
use App\Models\BusinessType;
use App\Models\OrderPriceTier;
use App\Models\PricingDiscount;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Motor de cálculo del cotizador de ChatbotGO.
 *
 * El navegador envía identificadores y cantidades; los precios salen siempre
 * de la base de datos. La monetización va por funcionalidades, módulos,
 * sucursales y números conectados: nunca por cantidad de pedidos.
 *
 * Reglas que implementa:
 *   1. Un paquete se selecciona una sola vez.
 *   2. Los paquetes superiores incluyen a los anteriores.
 *   3. Un módulo incluido en el paquete nunca se cobra.
 *   4. Las dependencias se resuelven automáticamente y se explican.
 *   5. No se duplican implementaciones ni mensualidades.
 *   6. Lo que no tiene precio aprobado se marca como pendiente, no se inventa.
 *   7. El consumo por pedidos, cuando la modalidad lo cobra, es escalonado y
 *      se presenta siempre aparte del cargo fijo.
 */
class QuotationCalculator
{
    /** Unidades de escalabilidad y la clave con la que llegan del cliente. */
    private const SCALABILITY = [
        'branch' => 'branches',
        'brand' => 'brands',
        'whatsapp' => 'whatsapp_numbers',
        'training' => 'trainings',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $businessType = $this->resolveBusinessType($input['business_type_id'] ?? null);
        $plan = $this->resolvePlan($input['plan_id'] ?? null);
        $billing = $this->resolveBillingModel($input['billing_model_id'] ?? null);

        $catalogue = $this->catalogue();
        $selection = $this->resolveSelection($input['items'] ?? [], $catalogue);
        $dependencies = $this->resolveDependencies($selection, $plan, $catalogue);

        $lines = collect();
        $order = 0;

        $this->addPackageLine($lines, $order, $plan);
        $this->addModuleLines($lines, $order, $dependencies['modules'], $plan, $catalogue, $dependencies['auto_added']);
        $scalability = $this->addScalabilityLines($lines, $order, $input['scalability'] ?? []);

        $setupSubtotal = $this->sum($lines, 'setup_price');
        $monthlySubtotal = $this->sum($lines, 'monthly_price');

        // Una modalidad sin cargo fijo lleva todo lo recurrente al consumo.
        if ($billing && ! $billing->charges_monthly) {
            $monthlySubtotal = 0.0;
        }

        $usage = $billing && $billing->charges_usage
            ? $this->usage($billing, $input, $scalability)
            : null;

        $pricingPublished = (bool) SiteSetting::get('pricing_published', false);
        $chargeable = $lines->reject(fn (array $line) => $line['is_included']);
        $unpriced = $chargeable->reject(fn (array $line) => $line['is_priced']);

        // El consumo sin tarifas cargadas deja la propuesta incompleta igual
        // que un módulo sin precio.
        $usagePending = $usage !== null && ! $usage['is_priced'];

        $isPriced = $pricingPublished && $chargeable->isNotEmpty() && $unpriced->isEmpty() && ! $usagePending;
        $hasEstimate = $pricingPublished && $chargeable->contains(fn (array $line) => $line['is_priced']);

        $discounts = $hasEstimate
            ? $this->resolveDiscounts($plan, $chargeable, $setupSubtotal, $monthlySubtotal)
            : collect();

        $setupDiscount = (float) $discounts->sum('setup_amount');
        $monthlyDiscount = (float) $discounts->sum('monthly_amount');

        $setupTotal = (float) max(0, round($setupSubtotal - $setupDiscount, 2));
        $monthlyTotal = (float) max(0, round($monthlySubtotal - $monthlyDiscount, 2));

        $projectionMonths = max(1, (int) SiteSetting::get('quotation_projection_months', 12));
        $usageMonthly = $usage['monthly'] ?? 0.0;

        return [
            'business_type' => $businessType ? [
                'id' => $businessType->id,
                'name' => $businessType->name,
                'slug' => $businessType->slug,
            ] : null,
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'level' => $plan->level,
                'headline' => $plan->headline,
                'included_item_slugs' => $plan->included_item_slugs ?? [],
            ] : null,
            'billing_model' => $billing ? [
                'id' => $billing->id,
                'name' => $billing->name,
                'slug' => $billing->slug,
                'type' => $billing->type,
                'charges_monthly' => $billing->charges_monthly,
                'charges_usage' => $billing->charges_usage,
            ] : null,
            'usage' => $usage,
            'lines' => $lines->values()->all(),
            'scalability' => $scalability,
            'dependencies' => [
                'auto_added' => $dependencies['auto_added'],
                'notes' => $dependencies['notes'],
            ],
            'currency' => $plan?->currency ?? SiteSetting::get('pricing_currency', 'USD'),
            'is_priced' => $isPriced,
            'has_estimate' => $hasEstimate,
            'unpriced_count' => $unpriced->count(),
            'pricing_published' => $pricingPublished,
            'setup_subtotal' => round($setupSubtotal, 2),
            'monthly_subtotal' => round($monthlySubtotal, 2),
            'setup_discount' => round($setupDiscount, 2),
            'monthly_discount' => round($monthlyDiscount, 2),
            'setup_total' => $setupTotal,
            'monthly_total' => $monthlyTotal,
            'usage_monthly' => $usage === null ? null : round($usageMonthly, 2),
            // Lo fijo más el consumo estimado de un mes tipo.
            'monthly_estimated_total' => round($monthlyTotal + $usageMonthly, 2),
            // Primer pago: implementación más la primera mensualidad. El
            // consumo se factura vencido, así que no entra aquí.
            'first_payment' => round($setupTotal + $monthlyTotal, 2),
            // Proyección del primer año, sin cargos externos ni impuestos.
            'projection_months' => $projectionMonths,
            'year_projection' => round($setupTotal + (($monthlyTotal + $usageMonthly) * $projectionMonths), 2),
            'discounts' => $discounts->values()->all(),
            'conditions' => $this->conditions(),
            'external_costs' => $this->externalCosts(),
            'validity_days' => (int) SiteSetting::get('quotation_validity_days', 15),
        ];
    }

    /** @return Collection<string, PricingItem> */
    private function catalogue(): Collection
    {
        return PricingItem::query()
            ->active()
            ->current()
            ->ordered()
            ->get()
            ->keyBy('slug');
    }

    private function resolveBusinessType(mixed $id): ?BusinessType
    {
        if (blank($id)) {
            return null;
        }

        $type = BusinessType::query()->active()->find($id);

        if (! $type) {
            throw ValidationException::withMessages([
                'business_type_id' => 'El tipo de negocio seleccionado no está disponible.',
            ]);
        }

        return $type;
    }

    private function resolvePlan(mixed $id): ?PricingPlan
    {
        if (blank($id)) {
            return null;
        }

        $plan = PricingPlan::query()->active()->current()->find($id);

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_id' => 'El paquete seleccionado no está disponible.',
            ]);
        }

        return $plan;
    }

    /**
     * Modalidad de cobro elegida.
     *
     * Si no llega ninguna se usa la recomendada, y en su defecto la primera
     * activa: el cotizador nunca se queda sin una forma de cobrar.
     */
    private function resolveBillingModel(mixed $id): ?BillingModel
    {
        $available = BillingModel::query()->active()->current()->ordered()->get();

        if (blank($id)) {
            return $available->firstWhere('is_recommended', true) ?? $available->first();
        }

        $model = $available->firstWhere('id', (int) $id);

        if (! $model) {
            throw ValidationException::withMessages([
                'billing_model_id' => 'La forma de cobro seleccionada no está disponible.',
            ]);
        }

        return $model;
    }

    /**
     * Consumo por pedidos del mes.
     *
     * El cliente declara cuántos pedidos hace **cada sucursal**; el total sale
     * de multiplicar por las sucursales contratadas. Según la modalidad, la
     * escalera de tramos corre por sucursal o sobre el total de la cuenta.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $scalability
     * @return array<string, mixed>
     */
    private function usage(BillingModel $billing, array $input, array $scalability): array
    {
        $branches = max(1, (int) ($scalability['branches']['total'] ?? 1));
        $perBranch = $billing->countsVolumePerBranch();

        $ordersPerBranch = max(0, (int) ($input['orders_per_branch'] ?? 0));
        $totalOrders = $ordersPerBranch * $branches;
        $included = max(0, (int) $billing->included_orders);

        $tiers = $billing->activeTiers()->ordered()->get();

        // Una escalera por sucursal, o una sola sobre el volumen de la cuenta.
        $ladder = $perBranch
            ? $this->graduate($tiers, $ordersPerBranch, $included)
            : $this->graduate($tiers, $totalOrders, $included);

        $multiplier = $perBranch ? $branches : 1;

        $rows = array_map(fn (array $row) => [
            'label' => $row['label'],
            'from' => $row['from'],
            'to' => $row['to'],
            'orders' => $row['orders'] * $multiplier,
            'orders_per_branch' => $row['orders'],
            'price_per_order' => $row['price_per_order'],
            'amount' => round($row['amount'] * $multiplier, 2),
        ], $ladder['lines']);

        $chargeable = $perBranch
            ? max(0, $ordersPerBranch - $included) * $branches
            : max(0, $totalOrders - $included);

        $covered = $ladder['covered'] * $multiplier;
        $subtotal = round(array_sum(array_column($rows, 'amount')), 2);

        $minimum = $billing->min_monthly !== null ? (float) $billing->min_monthly : null;
        $monthly = $minimum !== null ? max($subtotal, $minimum) : $subtotal;

        // Sin tramos cargados, o con pedidos que ningún tramo cubre, no hay
        // cifra honesta que mostrar.
        $isPriced = $tiers->isNotEmpty() && $covered === $chargeable;

        return [
            'model' => [
                'id' => $billing->id,
                'name' => $billing->name,
                'slug' => $billing->slug,
            ],
            'basis' => $perBranch ? 'branch' : 'total',
            'branches' => $branches,
            'orders_per_branch' => $ordersPerBranch,
            'total_orders' => $totalOrders,
            'included_orders' => $included,
            'chargeable_orders' => $chargeable,
            'uncovered_orders' => max(0, $chargeable - $covered),
            'tiers' => $rows,
            'subtotal' => $subtotal,
            'min_monthly' => $minimum,
            'minimum_applied' => $minimum !== null && $subtotal < $minimum,
            'monthly' => $isPriced ? round($monthly, 2) : 0.0,
            'average_per_order' => $isPriced && $chargeable > 0
                ? round($monthly / $chargeable, 4)
                : null,
            'is_priced' => $isPriced,
        ];
    }

    /**
     * Reparte los pedidos del mes entre los tramos, de forma escalonada.
     *
     * Cada pedido paga el precio del tramo en el que cae: llegar al pedido 501
     * abarata ese pedido, nunca los 500 anteriores. Los pedidos incluidos
     * consumen los primeros lugares de la escalera sin generar cargo.
     *
     * @param  Collection<int, OrderPriceTier>  $tiers
     * @return array{lines: array<int, array<string, mixed>>, covered: int}
     */
    private function graduate(Collection $tiers, int $orders, int $included): array
    {
        $lines = [];
        $covered = 0;
        $first = $included + 1;

        foreach ($tiers as $tier) {
            $from = max($first, (int) $tier->from_orders);
            $to = $tier->to_orders === null ? $orders : min($orders, (int) $tier->to_orders);

            if ($to < $from) {
                continue;
            }

            $count = $to - $from + 1;
            $price = (float) $tier->price_per_order;

            $lines[] = [
                'label' => $tier->label ?: $tier->range(),
                'from' => $from,
                'to' => $to,
                'orders' => $count,
                'price_per_order' => $price,
                'amount' => round($count * $price, 2),
            ];

            $covered += $count;
        }

        return ['lines' => $lines, 'covered' => $covered];
    }

    /**
     * Módulos elegidos por el cliente, validados contra el catálogo.
     *
     * @param  array<int, array{id:int}|int>  $selected
     * @param  Collection<string, PricingItem>  $catalogue
     * @return Collection<int, PricingItem>
     */
    private function resolveSelection(array $selected, Collection $catalogue): Collection
    {
        $ids = collect($selected)
            ->map(fn ($row) => is_array($row) ? ($row['id'] ?? null) : $row)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($ids->isEmpty()) {
            return collect();
        }

        $modules = $catalogue->filter(fn (PricingItem $item) => $ids->contains($item->id) && $item->group === 'module');

        $missing = $ids->diff($modules->pluck('id'));

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Alguno de los módulos seleccionados ya no está disponible. Actualiza la página e inténtalo de nuevo.',
            ]);
        }

        $unavailable = $modules->first(fn (PricingItem $item) => ! $item->is_available);

        if ($unavailable) {
            throw ValidationException::withMessages([
                'items' => sprintf('"%s" no se contrata por separado: llega dentro de un paquete.', $unavailable->name),
            ]);
        }

        return $modules->values();
    }

    /**
     * Aplica las dependencias entre módulos.
     *
     * Las obligatorias (requires) activan el módulo que falta; las
     * condicionales (requires_any) bloquean la combinación cuando ninguna de
     * las alternativas está presente.
     *
     * @param  Collection<int, PricingItem>  $selection
     * @param  Collection<string, PricingItem>  $catalogue
     * @return array{modules: Collection<int, PricingItem>, auto_added: array<int, array<string, string>>, notes: array<int, array<string, string>>}
     */
    private function resolveDependencies(Collection $selection, ?PricingPlan $plan, Collection $catalogue): array
    {
        $included = collect($plan?->included_item_slugs ?? []);
        $modules = $selection->keyBy('slug');
        $autoAdded = [];

        // Las dependencias obligatorias pueden encadenarse, así que se repite
        // hasta que no quede ninguna por resolver.
        $guard = 0;

        do {
            $added = false;

            foreach ($modules as $module) {
                foreach ($module->requires ?? [] as $slug) {
                    if ($included->contains($slug) || $modules->has($slug)) {
                        continue;
                    }

                    $required = $catalogue->get($slug);

                    if (! $required) {
                        continue;
                    }

                    $modules->put($slug, $required);
                    $autoAdded[] = [
                        'slug' => $slug,
                        'name' => $required->name,
                        'because_of' => $module->name,
                        'reason' => sprintf('%s necesita %s para funcionar, así que se activa automáticamente.', $module->name, $required->name),
                    ];
                    $added = true;
                }
            }

            $guard++;
        } while ($added && $guard < 10);

        // Dependencias condicionales: alguna de las alternativas tiene que estar.
        foreach ($modules as $module) {
            $alternatives = $module->requires_any ?? [];

            if ($alternatives === []) {
                continue;
            }

            $satisfied = collect($alternatives)
                ->contains(fn (string $slug) => $included->contains($slug) || $modules->has($slug));

            if ($satisfied) {
                continue;
            }

            $names = collect($alternatives)
                ->map(fn (string $slug) => $catalogue->get($slug)?->name ?? $slug)
                ->join(', ', ' o ');

            throw ValidationException::withMessages([
                'items' => sprintf('%s necesita al menos uno de estos canales: %s. Elige un paquete que lo incluya o añádelo a tu configuración.', $module->name, $names),
            ]);
        }

        // Observaciones operativas: informan, no bloquean.
        $notes = [];

        foreach ($modules as $module) {
            foreach ($module->notes ?? [] as $note) {
                $notes[] = ['module' => $module->name, 'note' => $note];
            }
        }

        return [
            'modules' => $modules->values(),
            'auto_added' => $autoAdded,
            'notes' => $notes,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $lines */
    private function addPackageLine(Collection $lines, int &$order, ?PricingPlan $plan): void
    {
        if (! $plan) {
            return;
        }

        $setup = $plan->setup_price !== null ? round((float) $plan->setup_price, 2) : null;
        $monthly = $plan->monthly_price !== null ? round((float) $plan->monthly_price, 2) : null;

        $lines->push([
            'pricing_item_id' => null,
            'slug' => $plan->slug,
            'name' => $plan->name,
            'description' => $plan->headline,
            'group' => 'package',
            'setup_price' => $setup,
            'monthly_price' => $monthly,
            'quantity' => 1,
            'is_included' => false,
            'is_auto_added' => false,
            'is_priced' => $setup !== null || $monthly !== null,
            'sort_order' => $order++,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     * @param  Collection<int, PricingItem>  $modules
     * @param  Collection<string, PricingItem>  $catalogue
     * @param  array<int, array<string, string>>  $autoAdded
     */
    private function addModuleLines(
        Collection $lines,
        int &$order,
        Collection $modules,
        ?PricingPlan $plan,
        Collection $catalogue,
        array $autoAdded,
    ): void {
        $includedSlugs = collect($plan?->included_item_slugs ?? []);
        $autoSlugs = collect($autoAdded)->pluck('slug');

        // Primero lo que ya trae el paquete: se lista, no se cobra.
        $includedSlugs->each(function (string $slug) use ($lines, &$order, $catalogue) {
            $module = $catalogue->get($slug);

            if (! $module) {
                return;
            }

            $lines->push([
                'pricing_item_id' => $module->id,
                'slug' => $module->slug,
                'name' => $module->name,
                'description' => $module->short_description,
                'group' => 'module',
                'setup_price' => 0.0,
                'monthly_price' => 0.0,
                'quantity' => 1,
                'is_included' => true,
                'is_auto_added' => false,
                'is_priced' => true,
                'sort_order' => $order++,
            ]);
        });

        // Después los contratados, saltando los que ya venían incluidos.
        foreach ($modules as $module) {
            if ($includedSlugs->contains($module->slug)) {
                continue;
            }

            $setup = $module->setup_price !== null ? round((float) $module->setup_price, 2) : null;
            $monthly = $module->monthly_price !== null ? round((float) $module->monthly_price, 2) : null;

            // Un módulo activado por dependencia que no se vende suelto llega
            // con el paquete: se muestra, pero no genera cargo.
            $byDependency = $autoSlugs->contains($module->slug);
            $notSellable = ! $module->is_available;

            $lines->push([
                'pricing_item_id' => $module->id,
                'slug' => $module->slug,
                'name' => $module->name,
                'description' => $module->short_description,
                'group' => 'module',
                'setup_price' => $notSellable ? 0.0 : $setup,
                'monthly_price' => $notSellable ? 0.0 : $monthly,
                'quantity' => 1,
                'is_included' => $notSellable,
                'is_auto_added' => $byDependency,
                'is_priced' => $notSellable || $setup !== null || $monthly !== null,
                'sort_order' => $order++,
            ]);
        }
    }

    /**
     * Sucursales, marcas, números de WhatsApp y capacitaciones.
     *
     * Solo se cobra lo que excede a lo incluido.
     *
     * @param  Collection<int, array<string, mixed>>  $lines
     * @param  array<string, mixed>  $requested
     * @return array<string, mixed>
     */
    private function addScalabilityLines(Collection $lines, int &$order, array $requested): array
    {
        $items = PricingItem::query()->active()->current()->group('scalability')->ordered()->get();
        $summary = [];

        foreach ($items as $item) {
            $key = self::SCALABILITY[$item->unit_kind] ?? null;

            if ($key === null) {
                continue;
            }

            $total = max(
                $item->min_quantity,
                min($item->max_quantity, (int) ($requested[$key] ?? $item->min_quantity))
            );

            $chargeable = max(0, $total - $item->included_quantity);

            $summary[$key] = [
                'slug' => $item->slug,
                'name' => $item->name,
                'unit' => $item->unit,
                'total' => $total,
                'included' => $item->included_quantity,
                'chargeable' => $chargeable,
                'setup_price' => $item->setup_price !== null ? (float) $item->setup_price : null,
                'monthly_price' => $item->monthly_price !== null ? (float) $item->monthly_price : null,
                'notes' => $item->notes ?? [],
            ];

            if ($chargeable < 1) {
                continue;
            }

            $setup = $item->setup_price !== null ? round((float) $item->setup_price * $chargeable, 2) : null;
            $monthly = $item->monthly_price !== null ? round((float) $item->monthly_price * $chargeable, 2) : null;

            $lines->push([
                'pricing_item_id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
                'description' => $item->included_quantity > 0
                    ? sprintf('%d en total · %d incluida(s) en el paquete', $total, $item->included_quantity)
                    : sprintf('%d %s', $total, $item->unit ?? 'unidad'),
                'group' => 'scalability',
                'setup_price' => $setup,
                'monthly_price' => $monthly,
                'quantity' => $chargeable,
                'is_included' => false,
                'is_auto_added' => false,
                'is_priced' => $setup !== null || $monthly !== null,
                'sort_order' => $order++,
            ]);
        }

        return $summary;
    }

    /** @param Collection<int, array<string, mixed>> $lines */
    private function sum(Collection $lines, string $field): float
    {
        return (float) $lines
            ->reject(fn (array $line) => $line['is_included'])
            ->sum(fn (array $line) => (float) ($line[$field] ?? 0));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $chargeable
     * @return Collection<int, array<string, mixed>>
     */
    private function resolveDiscounts(?PricingPlan $plan, Collection $chargeable, float $setup, float $monthly): Collection
    {
        $itemCount = $chargeable->where('group', '!=', 'package')->count();

        return PricingDiscount::query()
            ->active()
            ->current()
            ->where('is_automatic', true)
            ->ordered()
            ->get()
            ->filter(function (PricingDiscount $discount) use ($plan, $itemCount, $setup, $monthly) {
                if ($itemCount < $discount->min_items) {
                    return false;
                }

                $base = match ($discount->applies_to) {
                    'recurring' => $monthly,
                    'total' => $setup + $monthly,
                    default => $setup,
                };

                if ($discount->min_subtotal !== null && $base < (float) $discount->min_subtotal) {
                    return false;
                }

                if (! empty($discount->plan_slugs)) {
                    return $plan !== null && in_array($plan->slug, $discount->plan_slugs, true);
                }

                return true;
            })
            ->map(function (PricingDiscount $discount) use ($setup, $monthly) {
                $apply = function (float $base) use ($discount): float {
                    if ($base <= 0) {
                        return 0.0;
                    }

                    return $discount->type === 'percentage'
                        ? round($base * ((float) $discount->value / 100), 2)
                        : min($base, round((float) $discount->value, 2));
                };

                $setupAmount = in_array($discount->applies_to, ['setup', 'total'], true) ? $apply($setup) : 0.0;
                $monthlyAmount = in_array($discount->applies_to, ['recurring', 'total'], true) ? $apply($monthly) : 0.0;

                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'description' => $discount->description,
                    'type' => $discount->type,
                    'value' => (float) $discount->value,
                    'applies_to' => $discount->applies_to,
                    'setup_amount' => $setupAmount,
                    'monthly_amount' => $monthlyAmount,
                ];
            })
            ->filter(fn (array $d) => $d['setup_amount'] > 0 || $d['monthly_amount'] > 0);
    }

    /** @return array<int, string> */
    private function conditions(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) SiteSetting::get('quotation_conditions')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Servicios de terceros que se facturan aparte.
     *
     * @return array<int, array<string, mixed>>
     */
    private function externalCosts(): array
    {
        $configured = SiteSetting::get('external_costs');

        return is_array($configured) ? $configured : [];
    }
}
