<?php

namespace Database\Seeders;

use App\Models\ContentModule;
use App\Models\ContentSection;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Módulos retirados al rediseñar una sección.
     *
     * Se borran por nombre interno para no tocar los que haya creado el
     * administrador en esa misma sección.
     *
     * @var array<string, array<int, string>>
     */
    private const REPLACED_MODULES = [
        // La presentación con tarjetas pasó a ser una muestra de pantallas.
        'plataforma' => [
            'about-canal-propio',
            'about-identidad',
            'about-operacion',
            'about-implementado',
        ],
    ];

    public function run(): void
    {
        $this->removeReplacedModules();

        foreach ($this->sections() as $index => $section) {
            $modules = $section['modules'] ?? [];
            unset($section['modules']);

            $section['sort_order'] = ($index + 1) * 10;

            $model = ContentSection::query()->updateOrCreate(['key' => $section['key']], $section);

            foreach ($modules as $position => $module) {
                $module['sort_order'] = ($position + 1) * 10;
                $module['content_section_id'] = $model->id;

                ContentModule::query()->updateOrCreate(
                    [
                        'content_section_id' => $model->id,
                        'internal_name' => $module['internal_name'],
                    ],
                    $module
                );
            }
        }
    }

    private function removeReplacedModules(): void
    {
        foreach (self::REPLACED_MODULES as $sectionKey => $names) {
            $section = ContentSection::query()->where('key', $sectionKey)->first();

            if ($section) {
                $section->modules()->whereIn('internal_name', $names)->delete();
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(): array
    {
        return [
            [
                'key' => 'header',
                'name' => 'Header y navegación',
                'component' => 'header',
                'title' => 'HIVEDFOOD',
                'is_locked' => true,
                'buttons' => [
                    ['label' => 'Solicitar demostración', 'url' => '#contacto', 'style' => 'primary'],
                ],
                'settings' => ['sticky' => true, 'show_whatsapp' => true],
            ],
            [
                'key' => 'hero',
                'name' => 'Hero con banners dinámicos',
                'component' => 'hero',
                'title' => 'Banners dinámicos',
                'is_locked' => true,
                'settings' => [
                    'autoplay' => true,
                    'show_indicators' => true,
                    'show_arrows' => true,
                    // Eventos ilustrativos sobre la maqueta del producto.
                    'chips' => [
                        ['icon' => 'chat', 'label' => 'Nuevo pedido', 'detail' => 'WhatsApp · #047'],
                        ['icon' => 'receipt', 'label' => 'Comanda impresa', 'detail' => 'Cocina · #045'],
                    ],
                    'channels' => ['WhatsApp', 'Tienda en línea', 'Kiosco', 'Mostrador', 'Marketplaces'],
                ],
            ],
            [
                'key' => 'plataforma',
                'name' => 'El producto en pantalla',
                'component' => 'showcase',
                'eyebrow' => 'Así funciona',
                'title' => 'Todos tus canales de venta terminan en un solo pedido',
                'description' => 'HIVEDFOOD es la plataforma de pedidos de tu negocio gastronómico: WhatsApp, tienda en línea, kiosco, mostrador y marketplaces entran al mismo tablero, con la misma numeración y la misma cocina. Tu equipo trabaja en una pantalla, no en cinco.',
                'buttons' => [
                    ['label' => 'Ver los módulos', 'url' => '#funcionalidades', 'style' => 'primary'],
                    ['label' => 'Conocer demostraciones', 'url' => '#demostraciones', 'style' => 'ghost'],
                ],
                'settings' => ['background' => 'ivory'],
                'modules' => [
                    [
                        'internal_name' => 'pantalla-tienda-movil',
                        'layout' => 'image_text',
                        'icon' => 'mobile',
                        'eyebrow' => 'Experiencia de compra',
                        'title' => 'Tu cliente pide desde el móvil en pocos toques',
                        'description' => 'La tienda se ve con tu marca, muestra tu carta real con sus opciones y extras, y deja el pedido armado sin instalar nada ni repetir datos.',
                        'bullets' => [
                            'Carta con categorías, opciones y extras',
                            'Carrito y pedido en la misma pantalla',
                            'Mismo recorrido desde WhatsApp o desde tu tienda',
                        ],
                        'settings' => ['screen' => 'store'],
                    ],
                    [
                        'internal_name' => 'pantalla-pedidos',
                        'layout' => 'image_text',
                        'icon' => 'flow',
                        'eyebrow' => 'Gestión de pedidos',
                        'title' => 'Todos los canales, un solo tablero',
                        'description' => 'WhatsApp, tienda, kiosco, mostrador y marketplaces caen en la misma pantalla, cada pedido con su número del día, su canal de origen y su estado.',
                        'bullets' => [
                            'Número del día por sucursal',
                            'Canal de origen visible en cada pedido',
                            'Estados controlados de principio a fin',
                        ],
                        'settings' => ['screen' => 'orders'],
                    ],
                    [
                        'internal_name' => 'pantalla-catalogo',
                        'layout' => 'image_text',
                        'icon' => 'menu',
                        'eyebrow' => 'Catálogo',
                        'title' => 'Tu carta como funciona de verdad',
                        'description' => 'Categorías, productos, grupos de opciones y extras con sus reglas: elige uno, hasta cuatro, con su precio. Cambias un precio y está cambiado en todos tus canales.',
                        'bullets' => [
                            'Grupos de opciones con reglas de selección',
                            'Precios por variación y extras',
                            'Disponibilidad y stock por sucursal',
                        ],
                        'settings' => ['screen' => 'catalog'],
                    ],
                    [
                        'internal_name' => 'pantalla-cocina',
                        'layout' => 'image_text',
                        'icon' => 'receipt',
                        'eyebrow' => 'Operación del local',
                        'title' => 'La cocina sabe qué va primero',
                        'description' => 'El tablero por columnas muestra qué está por preparar, qué se está cocinando y qué está listo para retirar, con la comanda impresa automáticamente.',
                        'bullets' => [
                            'Por preparar, en preparación y listos',
                            'Comanda impresa sin intervención',
                            'Turnos cantados en la pantalla del local',
                        ],
                        'settings' => ['screen' => 'kitchen'],
                    ],
                ],
            ],
            [
                'key' => 'problemas',
                'name' => 'Problemas que resuelve',
                'component' => 'problems',
                'eyebrow' => 'El punto de partida',
                'title' => 'Vender comida por varios canales no debería costar tanto',
                'description' => 'La mayoría de los negocios gastronómicos terminan con los pedidos repartidos entre el chat, el teléfono, el mostrador y las apps, y nadie con la foto completa.',
                'settings' => [
                    'background' => 'charcoal',
                    'outcome' => 'Con HIVEDFOOD, todo eso entra por un solo tablero: el mismo pedido, la misma cocina y los mismos reportes.',
                ],
                'buttons' => [
                    ['label' => 'Ver cómo funciona', 'url' => '#plataforma', 'style' => 'primary'],
                ],
                'modules' => [
                    [
                        'internal_name' => 'problema-comisiones',
                        'layout' => 'card',
                        'icon' => 'percent',
                        'title' => 'Las apps se llevan el margen',
                        'description' => 'Las comisiones se comen la ganancia y el cliente nunca llega a ser realmente tuyo.',
                    ],
                    [
                        'internal_name' => 'problema-pedidos',
                        'layout' => 'card',
                        'icon' => 'chat',
                        'title' => 'Los pedidos llegan por todos lados',
                        'description' => 'Mensajes, llamadas y capturas de pantalla que alguien tiene que ordenar a mano.',
                    ],
                    [
                        'internal_name' => 'problema-catalogo',
                        'layout' => 'card',
                        'icon' => 'list',
                        'title' => 'Nadie sabe cuánto se vendió de verdad',
                        'description' => 'Con cada canal por separado, cerrar el día y cuadrar la caja es un trabajo manual.',
                    ],
                    [
                        'internal_name' => 'problema-imagen',
                        'layout' => 'card',
                        'icon' => 'clock',
                        'title' => 'La cocina se enreda en hora pico',
                        'description' => 'Comandas dictadas a viva voz, turnos que se pierden y clientes preguntando por su pedido.',
                    ],
                ],
            ],

            // ----------------------------------------- líneas de entrada ----
            [
                'key' => 'canales',
                'name' => 'Canales de entrada de pedidos',
                'component' => 'channels',
                'eyebrow' => 'Por dónde entran los pedidos',
                'title' => 'Siete líneas de entrada, un solo pedido',
                'description' => 'Cada canal que abras suma ventas sin sumar trabajo: todos llegan al mismo tablero, con la misma numeración del día y la misma comanda para la cocina.',
                'settings' => [
                    'background' => 'white',
                    'converge' => [
                        ['icon' => 'list', 'title' => 'Un solo tablero', 'description' => 'Tu equipo atiende todos los pedidos en la misma pantalla.'],
                        ['icon' => 'receipt', 'title' => 'La misma comanda', 'description' => 'La cocina recibe e imprime igual, venga de donde venga el pedido.'],
                        ['icon' => 'chart', 'title' => 'Los mismos reportes', 'description' => 'Cierras el día con todos los canales ya sumados.'],
                    ],
                ],
                'modules' => [
                    [
                        'internal_name' => 'canal-whatsapp',
                        'layout' => 'card',
                        'icon' => 'chat',
                        'eyebrow' => 'El canal principal',
                        'title' => 'Chatbot de WhatsApp',
                        'description' => 'El cliente pide donde ya está. Conversación completa con catálogo y carrito dentro del chat, botones y listas para elegir sin escribir, y traspaso a una persona del equipo cuando hace falta.',
                        'bullets' => [
                            'Catálogo y carrito dentro de la conversación',
                            'Pedido completo en una sola pantalla del chat',
                            'Traspaso a un agente humano',
                            'Varios números de WhatsApp por negocio',
                        ],
                    ],
                    [
                        'internal_name' => 'canal-tienda',
                        'layout' => 'card',
                        'icon' => 'storefront',
                        'title' => 'Tienda en línea con tu dominio',
                        'description' => 'Catálogo navegable con carrito, tu marca completa y tu propio dominio. Cuentas de cliente con direcciones guardadas y cálculo del envío según la dirección.',
                        'bullets' => ['Tu marca y tu dominio', 'Cuentas y direcciones del cliente', 'Cálculo del envío'],
                    ],
                    [
                        'internal_name' => 'canal-kiosco',
                        'layout' => 'card',
                        'icon' => 'mobile',
                        'title' => 'Kiosco de autoservicio',
                        'description' => 'La misma carta en una tablet dentro del local: el cliente elige comer aquí o para llevar y pide sin hacer fila.',
                        'bullets' => ['Comer aquí o para llevar', 'Sucursal fija', 'Sin fila en caja'],
                    ],
                    [
                        'internal_name' => 'canal-pos',
                        'layout' => 'card',
                        'icon' => 'card',
                        'title' => 'Mostrador (punto de venta)',
                        'description' => 'El cajero toma el pedido en una pantalla pensada para ir rápido, y ese pedido entra al mismo flujo que los demás.',
                        'bullets' => ['Pantalla para el cajero', 'Sesión por persona', 'Mismo flujo de cocina'],
                    ],
                    [
                        'internal_name' => 'canal-mayoreo',
                        'layout' => 'card',
                        'icon' => 'list',
                        'title' => 'Mayoreo y eventos',
                        'description' => 'Para pedidos grandes, un formulario amplio con enlace de un solo uso que envía el propio bot. Sin cadenas de mensajes para armar una lista larga.',
                        'bullets' => ['Enlace de un solo uso', 'Listas largas sin fricción'],
                    ],
                    [
                        'internal_name' => 'canal-marketplaces',
                        'layout' => 'card',
                        'icon' => 'globe',
                        'title' => 'Marketplaces',
                        'description' => 'Los pedidos de PedidosYa, Uber Eats y Rappi entran al mismo tablero, con credenciales por sucursal. Dejas de saltar entre tablets.',
                        'bullets' => ['PedidosYa, Uber Eats y Rappi', 'Credenciales por sucursal', 'Reintento de pedidos fallidos'],
                    ],
                    [
                        'internal_name' => 'canal-api',
                        'layout' => 'card',
                        'icon' => 'layers',
                        'title' => 'Sistemas externos',
                        'description' => 'Si ya tienes una app o un sistema propio, puede enviar sus pedidos a la plataforma y aparecen como cualquier otro.',
                        'bullets' => ['Entrada para sistemas propios', 'Mismo tratamiento que el resto'],
                    ],
                ],
            ],

            // -------------------------------------- cómo se gestiona ----
            [
                'key' => 'flujo-pedido',
                'name' => 'Flujo del pedido',
                'component' => 'order-flow',
                'eyebrow' => 'Cómo se gestiona',
                'title' => 'Del “quiero pedir” al pedido entregado',
                'description' => 'El pedido avanza por estados controlados: nadie puede saltarse un paso, el inventario se reserva al confirmar y cada movimiento queda registrado. Así se ve un día normal en un local de comida rápida.',
                'settings' => [
                    'background' => 'charcoal',
                    'notes' => [
                        'El inventario se reserva al confirmar el pedido y se libera si se cancela.',
                        'Cada cancelación queda tipificada: la pidió el cliente, se venció el tiempo de espera, la hizo un operador o la reportó el repartidor.',
                        'Cada cambio de estado avisa al cliente por el mismo canal donde pidió.',
                    ],
                ],
                'modules' => [
                    [
                        'internal_name' => 'estado-recibido',
                        'layout' => 'stat',
                        'value' => '01',
                        'icon' => 'chat',
                        'title' => 'Recibido',
                        'description' => 'Entra por cualquier canal y toma su número del día en la sucursal que lo atiende.',
                    ],
                    [
                        'internal_name' => 'estado-pago',
                        'layout' => 'stat',
                        'value' => '02',
                        'icon' => 'card',
                        'title' => 'Pago verificado',
                        'description' => 'Si paga por transferencia, tu equipo valida el comprobante; si paga con tarjeta, avanza solo al acreditarse.',
                    ],
                    [
                        'internal_name' => 'estado-confirmado',
                        'layout' => 'stat',
                        'value' => '03',
                        'icon' => 'check-badge',
                        'title' => 'Confirmado',
                        'description' => 'El local acepta el pedido y se reserva el inventario de lo que se va a preparar.',
                    ],
                    [
                        'internal_name' => 'estado-preparacion',
                        'layout' => 'stat',
                        'value' => '04',
                        'icon' => 'receipt',
                        'title' => 'En preparación',
                        'description' => 'Aparece en la pantalla de cocina y la comanda se imprime automáticamente.',
                    ],
                    [
                        'internal_name' => 'estado-listo',
                        'layout' => 'stat',
                        'value' => '05',
                        'icon' => 'sparkle',
                        'title' => 'Listo',
                        'description' => 'El turno se canta en la pantalla del local y el cliente recibe su aviso.',
                    ],
                    [
                        'internal_name' => 'estado-camino',
                        'layout' => 'stat',
                        'value' => '06',
                        'icon' => 'map',
                        'title' => 'En camino',
                        'description' => 'Solo para delivery: se asigna a un repartidor, que confirma la entrega desde su teléfono.',
                    ],
                    [
                        'internal_name' => 'estado-entregado',
                        'layout' => 'stat',
                        'value' => '07',
                        'icon' => 'star',
                        'title' => 'Entregado',
                        'description' => 'Se cierra el pedido, se le pide calificación al cliente y suma a los reportes del día.',
                    ],
                ],
            ],

            [
                'key' => 'beneficios',
                'name' => 'Beneficios de la plataforma',
                'component' => 'benefits',
                'eyebrow' => 'Por qué HIVEDFOOD',
                'title' => 'Lo que cambia cuando todo entra por un solo lugar',
                'settings' => ['background' => 'ivory'],
                'modules' => [
                    [
                        'internal_name' => 'beneficio-margen',
                        'layout' => 'card',
                        'icon' => 'coins',
                        'title' => 'Vendes sin intermediarios',
                        'description' => 'Tu canal propio no paga comisión por transacción, y los marketplaces pasan a ser un complemento, no tu única puerta.',
                    ],
                    [
                        'internal_name' => 'beneficio-clientes',
                        'layout' => 'card',
                        'icon' => 'users',
                        'title' => 'Tus clientes son tuyos',
                        'description' => 'Sabes quién compra, qué pide y cada cuánto vuelve, y puedes hablarle por WhatsApp cuando lo decidas.',
                    ],
                    [
                        'internal_name' => 'beneficio-control',
                        'layout' => 'card',
                        'icon' => 'sliders',
                        'title' => 'Controlas el contenido',
                        'description' => 'Carta, precios, promociones y hasta el guion del bot se cambian desde el panel, sin depender de nadie.',
                    ],
                    [
                        'internal_name' => 'beneficio-experiencia',
                        'layout' => 'card',
                        'icon' => 'mobile',
                        'title' => 'Pedir es más fácil',
                        'description' => 'El cliente compra en el canal que ya usa, sin instalar nada y sin repetir sus datos cada vez.',
                    ],
                    [
                        'internal_name' => 'beneficio-operacion',
                        'layout' => 'card',
                        'icon' => 'flow',
                        'title' => 'La hora pico deja de doler',
                        'description' => 'Comandas impresas, turnos en pantalla y estados claros: la cocina sabe qué va primero sin que nadie grite.',
                    ],
                    [
                        'internal_name' => 'beneficio-crecimiento',
                        'layout' => 'card',
                        'icon' => 'growth',
                        'title' => 'Creces por módulos',
                        'description' => 'Empiezas por WhatsApp y vas sumando canales, sucursales e integraciones cuando el negocio lo pide.',
                    ],
                ],
            ],
            [
                'key' => 'funcionalidades',
                'name' => 'Módulos de la plataforma',
                'component' => 'features',
                'eyebrow' => 'Módulos principales',
                'title' => 'Las piezas con las que armas tu operación',
                'description' => 'Hay cosas que vienen con cualquier paquete y otras que añades solo si las necesitas. Así pagas por lo que de verdad usas.',
                'settings' => [
                    'background' => 'white',
                    'columns' => 3,
                    'group_by_category' => true,
                    'categories' => ['nucleo', 'operacion', 'cobros'],
                ],
            ],
            [
                'key' => 'integraciones',
                'name' => 'Integraciones',
                'component' => 'features',
                'eyebrow' => 'Se conecta con lo que ya usas',
                'title' => 'Integraciones que ahorran trabajo de verdad',
                'description' => 'La plataforma no vive aislada: factura, cuadra tu inventario y te deja hablarle a tus clientes sin salir de un solo sistema.',
                'settings' => [
                    'background' => 'ivory',
                    'columns' => 3,
                    'categories' => ['integraciones', 'administracion'],
                    'group_by_category' => true,
                ],
            ],
            [
                'key' => 'personalizacion',
                'name' => 'Personalización de tiendas',
                'component' => 'customization',
                'eyebrow' => 'Personalización',
                'title' => 'La misma plataforma, nunca la misma tienda',
                'description' => 'Configuramos la identidad visual de cada negocio para que su tienda y su bot se sientan propios desde el primer momento.',
                'settings' => ['background' => 'charcoal'],
                'modules' => [
                    [
                        'internal_name' => 'personalizacion-identidad',
                        'layout' => 'image_text',
                        'icon' => 'palette',
                        'title' => 'Colores, logotipo y tipografía',
                        'description' => 'Tu paleta y tu marca aplicadas de forma consistente en toda la tienda.',
                    ],
                    [
                        'internal_name' => 'personalizacion-catalogo',
                        'layout' => 'image_text',
                        'icon' => 'menu',
                        'title' => 'Catálogo con tu estructura',
                        'description' => 'Categorías, productos, opciones y extras organizados como funciona tu carta.',
                    ],
                    [
                        'internal_name' => 'personalizacion-banners',
                        'layout' => 'image_text',
                        'icon' => 'image',
                        'title' => 'Banners y promociones',
                        'description' => 'Destaca lanzamientos, combos o temporadas desde el panel, sin tocar código.',
                    ],
                    [
                        'internal_name' => 'personalizacion-contenido',
                        'layout' => 'image_text',
                        'icon' => 'text',
                        'title' => 'Contenido y textos',
                        'description' => 'Historia del negocio, horarios e información comercial siempre al día.',
                    ],
                ],
            ],
            [
                'key' => 'demostraciones',
                'name' => 'Galería de demostraciones',
                'component' => 'demos',
                'eyebrow' => 'Casos reales',
                'title' => 'Negocios que ya operan con HIVEDFOOD',
                'description' => 'Implementaciones en producción que muestran cómo se adapta la plataforma a cada marca.',
                'settings' => ['background' => 'white'],
            ],
            [
                'key' => 'como-funciona',
                'name' => 'Cómo funciona la implementación',
                'component' => 'steps',
                'eyebrow' => 'Implementación',
                'title' => 'De la primera conversación a tu primer pedido',
                'settings' => ['background' => 'ivory'],
                'modules' => [
                    [
                        'internal_name' => 'paso-1',
                        'layout' => 'stat',
                        'value' => '01',
                        'title' => 'Conversamos tu operación',
                        'description' => 'Entendemos cómo vendes hoy, por qué canales entran tus pedidos y qué necesitas resolver primero.',
                    ],
                    [
                        'internal_name' => 'paso-2',
                        'layout' => 'stat',
                        'value' => '02',
                        'title' => 'Configuramos tu cuenta',
                        'description' => 'Conectamos tu WhatsApp, aplicamos tu identidad visual y dejamos listas tus sucursales.',
                    ],
                    [
                        'internal_name' => 'paso-3',
                        'layout' => 'stat',
                        'value' => '03',
                        'title' => 'Cargamos tu carta',
                        'description' => 'Productos, opciones, extras, precios y horarios, con la estructura real de tu negocio.',
                    ],
                    [
                        'internal_name' => 'paso-4',
                        'layout' => 'stat',
                        'value' => '04',
                        'title' => 'Salimos al aire y acompañamos',
                        'description' => 'Capacitamos a tu equipo en cocina, caja y despacho, y te acompañamos las primeras semanas.',
                    ],
                ],
            ],
            [
                'key' => 'negocios',
                'name' => 'Tipos de negocios',
                'component' => 'business-types',
                'eyebrow' => 'Para quién es',
                'title' => 'Pensada para el negocio gastronómico en todas sus formas',
                'description' => 'La plataforma se adapta al formato de venta de cada local, desde una cafetería de barrio hasta una cadena de comida rápida con varias sucursales.',
                'settings' => ['background' => 'white'],
            ],
            [
                'key' => 'precios',
                'name' => 'Precios y formas de cobro',
                'component' => 'pricing',
                'eyebrow' => 'Precios',
                'title' => 'Paga por lo que usas, no por lo que vendes',
                'description' => 'Eliges un paquete, añades los módulos que tu operación necesite y conectas tus sucursales. Sin comisiones sobre tus ventas y sin cargos por cantidad de pedidos: vendas lo que vendas, tu mensualidad es la misma.',
                'settings' => [
                    'background' => 'ivory',
                ],
                'buttons' => [
                    ['label' => 'Arma tu plataforma', 'url' => '#cotizador', 'style' => 'primary'],
                ],
            ],
            [
                'key' => 'cotizador',
                'name' => 'Cotizador interactivo',
                'component' => 'quoter',
                'eyebrow' => 'Cotizador',
                'title' => 'Construye la plataforma que tu restaurante necesita',
                'description' => 'Selecciona tus funcionalidades, conecta tus sucursales y abre tus canales de venta desde un solo lugar. El cotizador calcula tu inversión inicial y tu mensualidad mientras configuras.',
                'settings' => ['background' => 'charcoal'],
            ],
            [
                'key' => 'testimonios',
                'name' => 'Testimonios',
                'component' => 'testimonials',
                'eyebrow' => 'Testimonios',
                'title' => 'Lo que dicen los negocios que ya lo usan',
                'is_active' => false,
                'settings' => ['background' => 'white'],
            ],
            [
                'key' => 'preguntas',
                'name' => 'Preguntas frecuentes',
                'component' => 'faq',
                'eyebrow' => 'Preguntas frecuentes',
                'title' => 'Lo que suelen preguntarnos antes de empezar',
                'settings' => ['background' => 'ivory'],
            ],
            [
                'key' => 'contacto',
                'name' => 'Llamada a la acción',
                'component' => 'cta',
                'eyebrow' => 'Siguiente paso',
                'title' => 'Veamos juntos cómo se vería tu operación',
                'description' => 'Agenda una demostración y te mostramos la plataforma funcionando con un negocio parecido al tuyo: un pedido entrando por WhatsApp, pasando por cocina y saliendo a reparto.',
                'settings' => ['background' => 'honey', 'show_form' => true],
                'buttons' => [
                    ['label' => 'Solicitar demostración', 'url' => '#contacto', 'style' => 'primary'],
                ],
            ],
            [
                'key' => 'footer',
                'name' => 'Footer',
                'component' => 'footer',
                'title' => 'HIVEDFOOD',
                'is_locked' => true,
                'settings' => ['show_social' => true],
            ],
        ];
    }
}
