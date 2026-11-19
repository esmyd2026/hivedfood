<?php

namespace Database\Seeders;

use App\Models\BillingModel;
use App\Models\OrderPriceTier;
use Illuminate\Database\Seeder;

/**
 * Modalidades de cobro de la plataforma.
 *
 * El catálogo (paquete, módulos y escalabilidad) define siempre la
 * implementación y la mensualidad. La modalidad solo añade, si el cliente la
 * elige, el consumo por pedidos procesados.
 *
 * Los tramos por pedido son **escalonados**: cada pedido paga el precio del
 * tramo en el que cae, no el del tramo alcanzado. Pasar de 500 a 501 pedidos
 * abarata el pedido 501, nunca los 500 anteriores.
 */
class BillingModelSeeder extends Seeder
{
    public function run(): void
    {
        $this->fixedMonthly();
        $this->monthlyPlusUsage();
        $this->commission();
        $this->retired();
    }

    /**
     * Borra la modalidad «por-pedido» de la primera versión.
     *
     * Cobraba el precio del tramo alcanzado a todo el mes y arrastraba sus
     * propios tramos, que aparecían duplicados junto a los vigentes. Solo se
     * elimina si ninguna cotización la usó.
     */
    private function retired(): void
    {
        BillingModel::query()
            ->where('slug', 'por-pedido')
            ->whereDoesntHave('quotations')
            ->each(fn (BillingModel $model) => $model->delete());
    }

    private function fixedMonthly(): void
    {
        BillingModel::query()->updateOrCreate(
            ['slug' => 'mensual'],
            [
                'name' => 'Mensualidad fija',
                'type' => 'monthly',
                'headline' => 'Pagas lo mismo todos los meses, vendas lo que vendas.',
                'description' => 'La mensualidad de tu paquete, tus módulos y tus sucursales. Sin cargo por pedido y sin topes de volumen: si tu mes es bueno, no pagas más.',
                'icon' => 'clock',
                'charges_monthly' => true,
                'charges_usage' => false,
                'included' => [
                    'Tarifa fija y previsible cada mes',
                    'Sin cargo por pedido ni por venta',
                    'Sin límite de pedidos al mes',
                    'Ideal si quieres presupuestar sin sorpresas',
                ],
                'conditions' => [
                    'La mensualidad se factura por adelantado.',
                    'La implementación es un pago único al inicio.',
                ],
                'is_active' => true,
                'sort_order' => 10,
            ]
        );
    }

    private function monthlyPlusUsage(): void
    {
        $model = BillingModel::query()->updateOrCreate(
            ['slug' => 'mensual-mas-uso'],
            [
                'name' => 'Mensualidad más consumo',
                'type' => 'per_order',
                'headline' => 'Una mensualidad menor y un consumo por pedido que baja con el volumen.',
                'description' => 'Además de la mensualidad de tu paquete, pagas por los pedidos que procesas. El precio es escalonado: los primeros pedidos del mes pagan su tarifa y solo los que superan cada tramo pagan la tarifa más baja.',
                'icon' => 'receipt',
                'charges_monthly' => true,
                'charges_usage' => true,
                // El volumen de cada sucursal corre su propia escalera.
                'volume_basis' => 'branch',
                'included_orders' => 0,
                'min_monthly' => null,
                'included' => [
                    'Pagas por los pedidos que de verdad procesas',
                    'Tramos escalonados: crecer nunca encarece lo ya recorrido',
                    'El volumen se cuenta por sucursal',
                    'El consumo se factura al cierre de cada mes',
                ],
                'conditions' => [
                    'Cada pedido paga el precio del tramo en el que cae, no el del tramo alcanzado.',
                    'El consumo del mes se factura vencido, junto a la mensualidad del mes siguiente.',
                ],
                'is_recommended' => true,
                'is_active' => true,
                'sort_order' => 20,
            ]
        );

        // Tarifas confirmadas por el equipo comercial.
        $tiers = [
            ['from_orders' => 1, 'to_orders' => 500, 'price_per_order' => 0.25, 'label' => 'Los primeros 500 pedidos', 'sort_order' => 10],
            ['from_orders' => 501, 'to_orders' => 1000, 'price_per_order' => 0.15, 'label' => 'Del 501 al 1.000', 'sort_order' => 20],
            ['from_orders' => 1001, 'to_orders' => null, 'price_per_order' => 0.10, 'label' => 'Del 1.001 en adelante', 'sort_order' => 30],
        ];

        foreach ($tiers as $tier) {
            OrderPriceTier::query()->updateOrCreate(
                ['billing_model_id' => $model->id, 'from_orders' => $tier['from_orders']],
                $tier + ['billing_model_id' => $model->id, 'is_active' => true]
            );
        }
    }

    private function commission(): void
    {
        BillingModel::query()->updateOrCreate(
            ['slug' => 'comision'],
            [
                'name' => 'Comisión por ventas',
                'type' => 'commission',
                'headline' => 'Un porcentaje de lo que vendes por la plataforma.',
                'description' => 'En lugar del consumo por pedido, se cobra un porcentaje sobre las ventas que entran por tus canales.',
                'icon' => 'percent',
                'charges_monthly' => true,
                'charges_usage' => false,
                'included' => [
                    'Pagas un porcentaje de lo que vendes',
                    'Se ajusta a tu temporada alta y baja',
                ],
                'conditions' => [
                    'El porcentaje se aplica sobre las ventas cerradas en la plataforma.',
                ],
                // Porcentaje pendiente de definición comercial: no se publica.
                'commission_percent' => null,
                'is_active' => false,
                'sort_order' => 30,
            ]
        );
    }
}
