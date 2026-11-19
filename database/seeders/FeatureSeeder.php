<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * Catálogo comercial de módulos de la plataforma.
 *
 * Solo contenido vendible: qué hace cada módulo para el negocio. Nada de
 * detalles de implementación. Las categorías agrupan la presentación en el
 * sitio: unas vienen con cualquier paquete y el resto se contrata aparte.
 */
class FeatureSeeder extends Seeder
{
    /** Slugs de la versión anterior del catálogo que ya no aplican. */
    private const OBSOLETE = [
        'tienda-digital',
        'catalogo-productos',
        'gestion-pedidos',
        'identidad-visual',
        'banners-contenido',
        'panel-administrativo',
        'experiencia-movil',
        'pagos-en-linea',
        'zonas-entrega',
        'cupones-promociones',
        'reportes-metricas',
        'multi-sucursal',
    ];

    public function run(): void
    {
        Feature::query()->whereIn('slug', self::OBSOLETE)->delete();

        foreach ($this->features() as $index => $feature) {
            $feature['sort_order'] = ($index + 1) * 10;

            Feature::query()->updateOrCreate(['slug' => $feature['slug']], $feature);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function features(): array
    {
        return [
            // ------------------------- viene con cualquier paquete
            [
                'name' => 'Motor de pedidos',
                'slug' => 'motor-de-pedidos',
                'category' => 'nucleo',
                'icon' => 'flow',
                'short_description' => 'Un solo pedido, sin importar por dónde entró.',
                'description' => 'Todos los canales desembocan en el mismo pedido: con su número del día por sucursal, sus estados controlados, su inventario reservado y su línea de tiempo completa. El equipo trabaja en una sola pantalla.',
                'bullets' => ['Numeración diaria por sucursal', 'Estados controlados', 'Línea de tiempo del pedido'],
                'is_highlighted' => true,
            ],
            [
                'name' => 'Catálogo de productos',
                'slug' => 'catalogo',
                'category' => 'nucleo',
                'icon' => 'menu',
                'short_description' => 'Tu carta real, con opciones, extras y variaciones.',
                'description' => 'Categorías, productos, grupos de opciones, extras y variaciones con su propio precio. Disponibilidad y stock por sucursal, fotos optimizadas y carga masiva para montar la carta en una sola pasada.',
                'bullets' => ['Opciones, extras y variaciones', 'Disponibilidad por sucursal', 'Carga masiva'],
                'is_highlighted' => true,
            ],
            [
                'name' => 'Varias sucursales y marcas',
                'slug' => 'sucursales',
                'category' => 'nucleo',
                'icon' => 'building',
                'short_description' => 'Cada local con su horario, su carta y su equipo.',
                'description' => 'Administra todos tus locales desde una sola cuenta: horarios, catálogo, precios y equipo por sucursal, con tu marca y tu dominio propios.',
                'bullets' => ['Horario por sucursal', 'Catálogo por local', 'Equipo por sucursal'],
                'is_highlighted' => true,
            ],

            // ------------------------------------------------------ operación
            [
                'name' => 'Cocina y comandas',
                'slug' => 'cocina-comandas',
                'category' => 'operacion',
                'icon' => 'receipt',
                'short_description' => 'Tablero por columnas y comandas impresas automáticamente.',
                'description' => 'La cocina ve los pedidos por preparar, en preparación y listos en una pantalla completa, con impresión automática de la comanda y reinicio de turnos.',
                'bullets' => ['Tablero por columnas', 'Impresión automática', 'Reinicio de turnos'],
            ],
            [
                'name' => 'Delivery y repartidores',
                'slug' => 'delivery',
                'category' => 'operacion',
                'icon' => 'map',
                'short_description' => 'Costo por distancia real y entrega confirmada desde el celular.',
                'description' => 'Cobra el envío por tramos de distancia configurados por sucursal, asigna al local más cercano y despacha con repartidores que confirman la entrega desde su propio teléfono. Aviso automático de pedidos demorados.',
                'bullets' => ['Costo por tramos de distancia', 'Sucursal más cercana', 'Alerta de pedidos demorados'],
            ],
            [
                'name' => 'Pantalla de turnos para el local',
                'slug' => 'pantalla-tv',
                'category' => 'operacion',
                'icon' => 'mobile',
                'short_description' => 'Cartelera con tu publicidad y el llamado de turnos listos.',
                'description' => 'Convierte cualquier televisor del local en cartelera: tus videos o publicidad, y el llamado de los turnos que ya están listos para retirar.',
                'bullets' => ['Publicidad en video', 'Llamado de turnos'],
            ],
            [
                'name' => 'Reportes y analítica',
                'slug' => 'reportes',
                'category' => 'operacion',
                'icon' => 'chart',
                'short_description' => 'Ventas por sucursal, canal y producto, exportables.',
                'description' => 'Cuánto vendió cada sucursal, por qué canal entró cada pedido, qué producto se mueve y cómo rinden los repartidores. Todo exportable a Excel y PDF.',
                'bullets' => ['Por sucursal, canal y producto', 'Rendimiento de repartidores', 'Exportación a Excel y PDF'],
            ],
            [
                'name' => 'Calificaciones',
                'slug' => 'calificaciones',
                'category' => 'operacion',
                'icon' => 'star',
                'short_description' => 'El cliente califica el pedido por el mismo canal donde compró.',
                'description' => 'Al cerrarse el pedido, el cliente recibe un enlace por el mismo canal donde compró para calificar su pedido y al repartidor.',
                'bullets' => ['Enlace automático', 'Califica pedido y repartidor'],
            ],

            // --------------------------------------------------------- cobros
            [
                'name' => 'Transferencia con comprobante',
                'slug' => 'transferencia',
                'category' => 'cobros',
                'icon' => 'receipt',
                'short_description' => 'El cliente sube el comprobante y tu equipo lo verifica antes de despachar.',
                'description' => 'El cliente envía el comprobante por WhatsApp o desde la web, y tu equipo lo verifica contra el banco antes de que el pedido salga. Los comprobantes quedan archivados para contabilidad.',
                'bullets' => ['Comprobante por WhatsApp o web', 'Verificación antes de despachar', 'Archivo para contabilidad'],
            ],
            [
                'name' => 'Pago con tarjeta por enlace',
                'slug' => 'pago-tarjeta',
                'category' => 'cobros',
                'icon' => 'card',
                'short_description' => 'El pedido se confirma solo cuando el pago se acredita.',
                'description' => 'Envía un enlace de pago con tarjeta y el pedido avanza automáticamente al acreditarse. Sin confirmaciones manuales ni pedidos pagados a medias.',
                'bullets' => ['Enlace de pago', 'Confirmación automática'],
            ],
            [
                'name' => 'Efectivo contra entrega',
                'slug' => 'efectivo',
                'category' => 'cobros',
                'icon' => 'coins',
                'short_description' => 'El camino de siempre, registrado igual que el resto.',
                'description' => 'El pago en efectivo al recibir queda registrado en el mismo pedido y en los mismos reportes que los demás medios.',
                'bullets' => ['Sin pasos extra', 'Mismo registro y reportes'],
            ],

            // --------------------------------------------------- integraciones
            [
                'name' => 'Facturación electrónica',
                'slug' => 'facturacion-electronica',
                'category' => 'integraciones',
                'icon' => 'receipt',
                'short_description' => 'Emite la factura desde el propio pedido, integrado con Contífico.',
                'description' => 'Factura desde el detalle del pedido sin volver a escribir los datos: credenciales por empresa y por sucursal, emparejamiento de tus productos con los de Contífico, secuenciales y reporte de facturación.',
                'bullets' => ['Factura desde el pedido', 'Credenciales por sucursal', 'Reporte de facturación'],
                'is_highlighted' => true,
            ],
            [
                'name' => 'Sincronización de inventario',
                'slug' => 'sincronizacion-inventario',
                'category' => 'integraciones',
                'icon' => 'refresh',
                'short_description' => 'El stock del catálogo sigue a tu contabilidad, solo.',
                'description' => 'El inventario del catálogo se mantiene alineado con tu contabilidad de forma automática, con movimientos de stock auditables.',
                'bullets' => ['Actualización automática', 'Movimientos auditables'],
            ],
            [
                'name' => 'Campañas por WhatsApp',
                'slug' => 'campanas-whatsapp',
                'category' => 'integraciones',
                'icon' => 'chat',
                'short_description' => 'Envíos masivos con plantillas aprobadas y carritos recuperados.',
                'description' => 'Programa envíos masivos con plantillas aprobadas por Meta, sigue su estado y recupera automáticamente los carritos que el cliente dejó a medias.',
                'bullets' => ['Plantillas aprobadas', 'Envíos programados', 'Recuperación de carritos'],
            ],
            [
                'name' => 'Editor del flujo del bot',
                'slug' => 'editor-flujo-bot',
                'category' => 'integraciones',
                'icon' => 'sliders',
                'short_description' => 'Cambia el guion de la conversación sin programar.',
                'description' => 'Arma visualmente qué dice el bot y en qué orden, con versiones que publicas cuando estén listas. El guion de venta lo controlas tú.',
                'bullets' => ['Editor visual', 'Versiones publicables'],
            ],
            [
                'name' => 'Asistente con inteligencia artificial',
                'slug' => 'asistente-ia',
                'category' => 'integraciones',
                'icon' => 'spark',
                'short_description' => 'Responde preguntas y ayuda a cerrar el pedido.',
                'description' => 'Atiende las preguntas frecuentes de tus clientes y acompaña el cierre del pedido, con el alcance que tú definas y el consumo medido.',
                'bullets' => ['Alcance configurable', 'Consumo medido'],
            ],

            // ------------------------------------------------- administración
            [
                'name' => 'Clientes e historial',
                'slug' => 'clientes',
                'category' => 'administracion',
                'icon' => 'users',
                'short_description' => 'Quién te compra, qué pide y cada cuánto vuelve.',
                'description' => 'Ficha de cada cliente con su historial de pedidos, notas del equipo y datos de comportamiento para decidir a quién vale la pena hablarle.',
                'bullets' => ['Historial de pedidos', 'Notas del equipo', 'Exportación'],
            ],
            [
                'name' => 'Usuarios, roles y permisos',
                'slug' => 'roles-permisos',
                'category' => 'administracion',
                'icon' => 'shield',
                'short_description' => 'Cada persona ve solo lo que le toca.',
                'description' => 'Define qué puede ver y hacer cada rol, y a qué sucursales pertenece cada persona. El cajero no entra a los reportes y el repartidor solo ve sus entregas.',
                'bullets' => ['Permisos por rol', 'Usuarios por sucursal'],
            ],
            [
                'name' => 'Seguridad y accesos',
                'slug' => 'seguridad-accesos',
                'category' => 'administracion',
                'icon' => 'shield',
                'short_description' => 'Control de quién entra, desde dónde y cuándo.',
                'description' => 'Sesiones activas que puedes cerrar a distancia, registro de accesos y bloqueo de equipos o direcciones sospechosas.',
                'bullets' => ['Cierre remoto de sesiones', 'Registro de accesos', 'Bloqueos'],
            ],
            [
                'name' => 'Franquicias y multimarca',
                'slug' => 'franquicias',
                'category' => 'administracion',
                'icon' => 'layers',
                'short_description' => 'Varias marcas independientes en una misma cuenta.',
                'description' => 'Agrupa sucursales en marcas que funcionan por separado hacia el cliente pero se administran desde un solo lugar.',
                'bullets' => ['Marcas independientes', 'Administración unificada'],
            ],
        ];
    }
}
