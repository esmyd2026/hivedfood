<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\PricingDiscount;
use App\Models\PricingItem;
use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

/**
 * Catálogo comercial de ChatbotGO.
 *
 * Paquetes acumulativos P1–P5, módulos adicionales con sus dependencias y la
 * escalabilidad por sucursales, marcas y números de WhatsApp. Los precios son
 * los aprobados comercialmente; lo que no está aprobado se queda en NULL y el
 * cotizador lo muestra como pendiente en lugar de inventarlo.
 */
class PricingSeeder extends Seeder
{
    /** Canales capaces de generar pedidos: base de varias dependencias. */
    private const CHANNELS = [
        'chatbot-whatsapp',
        'micrositio',
        'kiosco',
        'punto-venta-pos',
        'marketplaces',
        'pedido-masivo',
    ];

    /** Estructura anterior que esta versión reemplaza. */
    private const OBSOLETE_ITEMS = [
        'conexion-whatsapp', 'config-identidad-visual', 'carga-catalogo', 'config-sucursales',
        'diseno-banners', 'dominio-personalizado', 'capacitacion', 'acompanamiento',
        'mod-chatbot-whatsapp', 'mod-tienda-online', 'mod-kiosco', 'mod-pos', 'mod-pedido-masivo',
        'mod-marketplaces', 'mod-cocina', 'mod-delivery', 'mod-pantalla-tv', 'mod-reportes',
        'mod-calificaciones', 'mod-transferencia', 'mod-pago-tarjeta', 'mod-facturacion',
        'mod-inventario', 'mod-campanas', 'mod-editor-flujo', 'mod-asistente-ia', 'mod-franquicias',
        'soporte-prioritario', 'actualizacion-contenido',
    ];

    private const OBSOLETE_PLANS = ['tienda-online', 'tienda-pedidos', 'solucion-integral', 'omnicanal', 'esencial-whatsapp'];

    public function run(): void
    {
        $this->businessTypes();
        $this->modules();
        $this->scalability();
        $this->packages();
        $this->discounts();

        PricingItem::query()->whereIn('slug', self::OBSOLETE_ITEMS)->delete();
        $this->retirePlans();
    }

    private function businessTypes(): void
    {
        $types = [
            ['name' => 'Comida rápida', 'slug' => 'comida-rapida', 'icon' => 'fast-food', 'short_description' => 'Alta rotación y hora pico intensa.'],
            ['name' => 'Hamburguesería', 'slug' => 'hamburgueseria', 'icon' => 'burger', 'short_description' => 'Combos, extras y armado a gusto.'],
            ['name' => 'Pizzería', 'slug' => 'pizzeria', 'icon' => 'pizza', 'short_description' => 'Tamaños, masas y mitad y mitad.'],
            ['name' => 'Restaurante', 'slug' => 'restaurante', 'icon' => 'restaurant', 'short_description' => 'Carta amplia y servicio en salón.'],
            ['name' => 'Cafetería', 'slug' => 'cafeteria', 'icon' => 'coffee', 'short_description' => 'Bebidas, pastelería y desayunos.'],
            ['name' => 'Heladería', 'slug' => 'heladeria', 'icon' => 'ice-cream', 'short_description' => 'Sabores, tamaños y toppings.'],
            ['name' => 'Cadena multisucursal', 'slug' => 'cadena', 'icon' => 'building', 'short_description' => 'Varios locales y marcas.'],
            ['name' => 'Otro negocio gastronómico', 'slug' => 'otro', 'icon' => 'more', 'short_description' => 'Cuéntanos cómo vendes hoy.', 'allow_free_text' => true],
        ];

        foreach ($types as $index => $type) {
            BusinessType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                $type + ['sort_order' => ($index + 1) * 10, 'is_active' => true]
            );
        }
    }

    /**
     * Catálogo de módulos.
     *
     * Los marcados como no disponibles no se venden sueltos: llegan dentro de
     * un paquete, pero siguen en el catálogo porque otras dependencias los
     * nombran.
     */
    private function modules(): void
    {
        foreach ($this->moduleCatalogue() as $index => $module) {
            $module['group'] = 'module';
            $module['sort_order'] = ($index + 1) * 10;
            $module['is_active'] = true;
            $module['currency'] = 'USD';

            PricingItem::query()->updateOrCreate(['slug' => $module['slug']], $module);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function moduleCatalogue(): array
    {
        return [
            // ----------------------------------------- incluidos en paquetes
            [
                'name' => 'Chatbot de WhatsApp',
                'slug' => 'chatbot-whatsapp',
                'category' => 'canales',
                'icon' => 'chat',
                'short_description' => 'Tus clientes piden por WhatsApp, sin salir de la conversación.',
                'description' => 'El cliente abre tu WhatsApp, ve la carta con fotos y precios, arma su pedido y lo confirma ahí mismo. Si necesita hablar con alguien, tu equipo toma la conversación.',
                'benefits' => ['La carta y el carrito dentro del chat', 'Tu equipo entra a la conversación cuando quiera', 'Puedes conectar más de un número'],
                'setup_price' => null,
                'monthly_price' => null,
                // Llega con cualquier paquete: no se vende por separado.
                'is_available' => false,
            ],
            [
                'name' => 'Reportes y analítica',
                'slug' => 'reportes-analitica',
                'category' => 'operacion',
                'icon' => 'chart',
                'short_description' => 'Saber cuánto vendiste, qué se pidió más y por dónde entró.',
                'description' => 'Un resumen claro de tus ventas: por local, por canal de venta y por producto. Lo descargas en Excel o PDF para revisarlo con tu contador.',
                'benefits' => ['Ventas por local, canal y producto', 'Descargable en Excel y PDF'],
                'requires_any' => self::CHANNELS,
                'is_available' => false,
            ],
            [
                'name' => 'Tu página de pedidos',
                'slug' => 'micrositio',
                'category' => 'canales',
                'icon' => 'storefront',
                'short_description' => 'Una página propia donde tus clientes compran directo.',
                'description' => 'Tu carta en una página con tu logo, tus colores y tu dirección web. El cliente llena el carrito, guarda su cuenta y sus direcciones, y te pide sin intermediarios.',
                'benefits' => ['Tu marca y tu dirección web', 'Carrito de compras', 'Tus clientes guardan sus datos y direcciones'],
                'is_available' => false,
            ],
            [
                'name' => 'Calificaciones',
                'slug' => 'calificaciones',
                'category' => 'operacion',
                'icon' => 'star',
                'short_description' => 'Tus clientes te dicen qué tal les fue con el pedido.',
                'description' => 'Cuando el pedido se cierra, el cliente recibe un enlace para calificar la comida y la entrega. Tú lees todas las respuestas en un solo lugar.',
                'benefits' => ['El cliente califica sin que se lo pidas', 'Califica la comida y la entrega'],
                'requires_any' => ['chatbot-whatsapp', 'micrositio'],
                'is_available' => false,
            ],

            // ------------------------------------------ módulos contratables
            [
                'name' => 'Facturación electrónica',
                'slug' => 'facturacion-electronica',
                'category' => 'integraciones',
                'icon' => 'receipt',
                'short_description' => 'Factura el pedido sin volver a escribir los datos.',
                'description' => 'Emite la factura directamente desde el pedido, con los datos del cliente ya cargados. Cada local puede facturar con sus propios datos y su propia numeración.',
                'benefits' => ['La factura sale del propio pedido', 'Cada local con sus datos', 'Resumen de todo lo facturado'],
                'setup_price' => 180,
                'monthly_price' => 25,
                'is_available' => true,
            ],
            [
                'name' => 'Inventario y stock',
                'slug' => 'inventario-stock',
                'category' => 'integraciones',
                'icon' => 'refresh',
                'short_description' => 'Saber qué te queda y que no se venda lo que ya se acabó.',
                'description' => 'Lleva la cuenta de lo que hay en cada local. Cuando un producto se agota deja de ofrecerse, y queda registrado quién movió qué.',
                'benefits' => ['Existencias por local', 'Lo agotado deja de venderse', 'Queda registrado cada movimiento'],
                'notes' => ['La sincronización automática con Contífico requiere el módulo de Facturación electrónica.'],
                'setup_price' => 150,
                'monthly_price' => 20,
                'is_available' => true,
            ],
            [
                'name' => 'Transferencias bancarias',
                'slug' => 'transferencias-bancarias',
                'category' => 'integraciones',
                'icon' => 'receipt',
                'short_description' => 'Cobra por transferencia sin perseguir comprobantes.',
                'description' => 'El cliente adjunta su comprobante al hacer el pedido y tu equipo lo revisa antes de mandar a preparar. Todo queda archivado para la contabilidad.',
                'benefits' => ['El cliente adjunta su comprobante', 'Se revisa antes de preparar el pedido', 'Archivo para tu contabilidad'],
                'requires_any' => self::CHANNELS,
                'setup_price' => 90,
                'monthly_price' => 15,
                'is_available' => true,
            ],
            [
                'name' => 'Cobro con tarjeta',
                'slug' => 'link-pago-tarjeta',
                'category' => 'integraciones',
                'icon' => 'card',
                'short_description' => 'Cobra con tarjeta enviando un enlace.',
                'description' => 'Le envías al cliente un enlace para pagar con tarjeta. Cuando el pago entra, el pedido avanza solo: nadie tiene que estar revisando.',
                'benefits' => ['Enlace de pago para el cliente', 'El pedido avanza al acreditarse el pago'],
                'requires_any' => self::CHANNELS,
                'setup_price' => 120,
                'monthly_price' => 15,
                'is_available' => true,
            ],
            [
                'name' => 'Campañas por WhatsApp',
                'slug' => 'campanas-automatizacion',
                'category' => 'automatizacion',
                'icon' => 'chat',
                'short_description' => 'Escríbele a tus clientes y recupera los pedidos que quedaron a medias.',
                'description' => 'Programa mensajes a tus clientes por WhatsApp con plantillas ya aprobadas, y recuérdale automáticamente a quien dejó su pedido sin terminar.',
                'benefits' => ['Mensajes programados a tus clientes', 'Recordatorio a quien dejó el pedido a medias', 'Ves cuántos llegaron y se leyeron'],
                'requires' => ['chatbot-whatsapp'],
                'setup_price' => 150,
                'monthly_price' => 25,
                'is_available' => true,
            ],
            [
                'name' => 'Asistente con IA',
                'slug' => 'asistente-ia',
                'category' => 'automatizacion',
                'icon' => 'spark',
                'short_description' => 'Un asistente que contesta las preguntas de siempre.',
                'description' => 'Responde lo que preguntan todos los días (horarios, precios, si hay domicilio) y acompaña al cliente hasta que cierra el pedido. Tú decides hasta dónde puede contestar.',
                'benefits' => ['Contesta las preguntas de siempre', 'Ayuda al cliente a terminar su pedido', 'Tú defines hasta dónde responde'],
                'requires' => ['chatbot-whatsapp'],
                'notes' => ['El consumo del asistente se factura por uso, aparte de la mensualidad.'],
                'setup_price' => 190,
                'monthly_price' => 35,
                'is_available' => true,
            ],
            [
                'name' => 'Apps de delivery',
                'slug' => 'marketplaces',
                'category' => 'canales',
                'icon' => 'globe',
                'short_description' => 'Los pedidos de las apps, en tu mismo tablero.',
                'description' => 'Lo que entra por PedidosYa, Uber Eats o Rappi aparece junto a tus otros pedidos y pasa por la misma cocina. Dejas de saltar entre tablets.',
                'benefits' => ['Todas las apps en una sola pantalla', 'Misma cocina y mismos reportes', 'Cada local con sus propias cuentas'],
                'notes' => ['Funciona de forma independiente: no necesita otros módulos.'],
                'setup_price' => 190,
                'monthly_price' => 25,
                'is_available' => true,
            ],
            [
                'name' => 'Pedidos para empresas y eventos',
                'slug' => 'pedido-masivo',
                'category' => 'canales',
                'icon' => 'list',
                'short_description' => 'Pedidos grandes, con su propio formulario.',
                'description' => 'Un formulario aparte para los pedidos de volumen, con un enlace que le envías al cliente y que sirve una sola vez.',
                'benefits' => ['Formulario pensado para listas largas', 'Enlace que sirve una sola vez'],
                'notes' => ['La distribución automática de los enlaces requiere el Chatbot de WhatsApp.'],
                'setup_price' => 120,
                'monthly_price' => 15,
                'is_available' => true,
            ],
            [
                'name' => 'Kiosco de autoservicio',
                'slug' => 'kiosco',
                'category' => 'canales',
                'icon' => 'mobile',
                'short_description' => 'Una tablet para que el cliente pida solo, sin hacer fila.',
                'description' => 'La carta en una tablet dentro del local. El cliente elige si come ahí o lleva, arma su pedido y pasa a caja.',
                'benefits' => ['Menos fila en caja', 'El cliente elige comer aquí o llevar'],
                'notes' => ['Funciona sin micrositio.'],
                'setup_price' => 190,
                'monthly_price' => 25,
                'is_available' => true,
            ],
            [
                'name' => 'Pantalla de turnos en el local',
                'slug' => 'pantalla-tv',
                'category' => 'operacion',
                'icon' => 'mobile',
                'short_description' => 'El televisor del local anuncia los pedidos listos.',
                'description' => 'Convierte una pantalla del local en cartelera: pasa tus videos y promociones, y avisa cuando un pedido está listo para retirar.',
                'benefits' => ['Llama los turnos sin gritar nombres', 'Pasa tus promociones en video'],
                'requires_any' => self::CHANNELS,
                'notes' => ['No depende de Cocina y comandas: le basta con un canal que genere pedidos.'],
                'setup_price' => 150,
                'monthly_price' => 20,
                'is_available' => true,
            ],
            [
                'name' => 'Pantalla de cocina',
                'slug' => 'cocina-comandas',
                'category' => 'operacion',
                'icon' => 'receipt',
                'short_description' => 'La cocina ve los pedidos en pantalla, sin papelitos.',
                'description' => 'Los pedidos aparecen en una pantalla ordenados por estado (por preparar, preparando y listos) y el ticket se imprime solo. Nadie tiene que dictar nada.',
                'benefits' => ['Pedidos ordenados por estado', 'El ticket se imprime solo', 'Los turnos se reinician cada día'],
                'requires_any' => self::CHANNELS,
                'setup_price' => 180,
                'monthly_price' => 25,
                'is_available' => true,
            ],
            [
                'name' => 'Caja para pedidos en mostrador',
                'slug' => 'punto-venta-pos',
                'category' => 'operacion',
                'icon' => 'card',
                'short_description' => 'Para cobrar también a quien llega al mostrador.',
                'description' => 'Una pantalla pensada para que el cajero tome el pedido rápido, con su propia sesión para saber quién atendió cada venta.',
                'benefits' => ['Pensada para ir rápido en hora pico', 'Cada cajero con su sesión'],
                'notes' => ['Funciona de forma independiente: no necesita otros módulos.'],
                'setup_price' => 120,
                'monthly_price' => 20,
                'is_available' => true,
            ],
            [
                'name' => 'Entregas con repartidores propios',
                'slug' => 'delivery-repartidores',
                'category' => 'operacion',
                'icon' => 'map',
                'short_description' => 'Tus propias entregas, cobradas según la distancia.',
                'description' => 'Defines cuánto cobras según lo lejos que viva el cliente, el pedido sale del local más cercano y el repartidor confirma la entrega desde su teléfono.',
                'benefits' => ['El envío se cobra según la distancia', 'Sale del local más cercano al cliente', 'El repartidor confirma desde su teléfono'],
                'requires_any' => ['chatbot-whatsapp', 'micrositio'],
                'notes' => ['Necesita un canal que capture la dirección del cliente.'],
                'setup_price' => 290,
                'monthly_price' => 45,
                'is_available' => true,
            ],
            [
                'name' => 'Varias marcas o franquicias',
                'slug' => 'franquicias-multimarca',
                'category' => 'operacion',
                'icon' => 'layers',
                'short_description' => 'Varias marcas o franquicias en una sola cuenta.',
                'description' => 'Cada marca se ve distinta para el cliente (su nombre, su carta, sus precios), pero tú las administras y las comparas desde un solo lugar.',
                'benefits' => ['Cada marca con su propia cara', 'Una sola administración', 'Comparas cómo va cada una'],
                'notes' => ['Cobra sentido cuando administras más de una sucursal o más de una marca.'],
                'setup_price' => 120,
                'monthly_price' => 20,
                'is_available' => true,
            ],
            [
                'name' => 'Trabaja con nosotros',
                'slug' => 'trabaja-con-nosotros',
                'category' => 'rrhh',
                'icon' => 'users',
                'short_description' => 'Recibe hojas de vida sin llenar tu WhatsApp.',
                'description' => 'Una página donde publicas tus vacantes y la gente deja su hoja de vida. Te llegan ordenadas al panel, no sueltas en el chat.',
                'benefits' => ['Página pública de vacantes', 'Las hojas de vida llegan ordenadas'],
                'setup_price' => 60,
                'monthly_price' => 10,
                'is_available' => true,
            ],
        ];
    }

    /** Sucursales, marcas, números de WhatsApp y capacitación. */
    private function scalability(): void
    {
        $items = [
            [
                'name' => 'Sucursal adicional',
                'slug' => 'sucursal-adicional',
                'unit_kind' => 'branch',
                'unit' => 'sucursal',
                'icon' => 'building',
                'short_description' => 'Otro local, con su carta, su horario y sus zonas de entrega.',
                'description' => 'La primera sucursal va incluida en el paquete. Cada local adicional suma su propia configuración.',
                'included_quantity' => 1,
                'setup_price' => 120,
                'monthly_price' => 25,
                'min_quantity' => 1,
                'max_quantity' => 200,
                'sort_order' => 10,
            ],
            [
                'name' => 'Marca adicional',
                'slug' => 'marca-adicional',
                'unit_kind' => 'brand',
                'unit' => 'marca',
                'icon' => 'layers',
                'short_description' => 'Otra marca, con su propia cara frente al cliente.',
                'description' => 'La primera marca va incluida. Cada marca adicional tiene su propia identidad de cara al cliente.',
                'included_quantity' => 1,
                'setup_price' => 290,
                'monthly_price' => 45,
                'min_quantity' => 1,
                'max_quantity' => 50,
                'sort_order' => 20,
            ],
            [
                'name' => 'Número de WhatsApp conectado',
                'slug' => 'numero-whatsapp',
                'unit_kind' => 'whatsapp',
                'unit' => 'número',
                'icon' => 'chat',
                'short_description' => 'Otro número de WhatsApp conectado a la misma bandeja.',
                'description' => 'Conecta más de un número y atiende todas las conversaciones desde el mismo lugar. Puedes dejar un número por local.',
                'included_quantity' => 1,
                // Tarifa pendiente de aprobación comercial: no se inventa.
                'setup_price' => null,
                'monthly_price' => null,
                'min_quantity' => 1,
                'max_quantity' => 50,
                'notes' => ['El consumo de conversaciones de Meta se factura aparte, según el uso real.'],
                'sort_order' => 30,
            ],
            [
                'name' => 'Capacitación adicional',
                'slug' => 'capacitacion-adicional',
                'unit_kind' => 'training',
                'unit' => 'sesión',
                'icon' => 'users',
                'short_description' => 'Otra sesión de acompañamiento para tu equipo.',
                'description' => 'La implementación incluye la capacitación inicial. Cada sesión adicional se cotiza aparte.',
                'included_quantity' => 0,
                // Tarifa pendiente de aprobación comercial.
                'setup_price' => null,
                'monthly_price' => null,
                'min_quantity' => 0,
                'max_quantity' => 20,
                'sort_order' => 40,
            ],
        ];

        foreach ($items as $item) {
            PricingItem::query()->updateOrCreate(
                ['slug' => $item['slug']],
                $item + [
                    'group' => 'scalability',
                    'currency' => 'USD',
                    'allow_quantity' => true,
                    'is_available' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Paquetes acumulativos.
     *
     * "included" es la lista comercial que se muestra; "included_item_slugs"
     * es la lista funcional, ya expandida, que usa el calculador para no
     * volver a cobrar lo que el paquete ya trae.
     */
    private function packages(): void
    {
        $levels = [
            1 => [
                'code' => 'P1',
                'name' => 'Esencial WhatsApp',
                'slug' => 'p1-esencial-whatsapp',
                'headline' => 'Recibe pedidos por WhatsApp sin que se te pierda ninguno.',
                'description' => 'Tu carta vive dentro de WhatsApp: el cliente la ve con fotos y precios, arma su pedido y lo confirma en la misma conversación. A ti te llega ordenado a una pantalla, con el detalle, la dirección y la forma de pago.',
                'icon' => 'chat',
                'setup_price' => 390,
                'monthly_price' => 69,
                'adds' => ['chatbot-whatsapp', 'reportes-analitica'],
                'bullets' => [
                    'Tu carta completa dentro de WhatsApp, con fotos y precios',
                    'El cliente arma su pedido solo; tú solo lo confirmas',
                    'Una pantalla donde ves todos los pedidos del día y en qué va cada uno',
                    'Tu equipo puede tomar la conversación cuando haga falta',
                    'Reportes de cuánto vendiste, qué se pidió más y a qué horas',
                    'Un local y un número de WhatsApp incluidos',
                ],
            ],
            2 => [
                'code' => 'P2',
                'name' => 'Tienda Web',
                'slug' => 'p2-tienda-web',
                'headline' => 'Súmale tu propia página de pedidos, con tu marca.',
                'description' => 'Además de WhatsApp, tus clientes te piden desde una página que es tuya: ven la carta, llenan el carrito y terminan el pedido sin pasar por nadie más. Tus clientes dejan de ser de la app de terceros y pasan a ser tuyos.',
                'icon' => 'storefront',
                'setup_price' => 590,
                'monthly_price' => 99,
                'adds' => ['micrositio', 'calificaciones'],
                'bullets' => [
                    'Todo lo de Esencial WhatsApp',
                    'Tu propia página de pedidos, con tu logo y tus colores',
                    'Carrito de compras y pedido terminado en pocos pasos',
                    'Tus clientes guardan su cuenta, sus datos y sus direcciones',
                    'Tus clientes califican el pedido y tú lees lo que opinan',
                ],
            ],
            3 => [
                'code' => 'P3',
                'name' => 'Operación en local',
                'slug' => 'p3-operacion-en-local',
                'headline' => 'Ordena la cocina, la caja y la entrega dentro del local.',
                'description' => 'Lo que se pide llega directo a la cocina en una pantalla, el cliente ve su turno en el televisor del local y tu cajero cobra desde el mismo sistema. Se acaban los papelitos y los pedidos mal pasados.',
                'icon' => 'receipt',
                'setup_price' => 890,
                'monthly_price' => 149,
                'adds' => ['cocina-comandas', 'pantalla-tv', 'kiosco', 'punto-venta-pos'],
                'bullets' => [
                    'Todo lo de Tienda Web',
                    'La cocina ve cada pedido en pantalla y marca cuándo está listo',
                    'Un televisor en el local que anuncia los turnos listos para retirar',
                    'Una tablet para que el cliente pida solo, sin hacer fila',
                    'Caja para cobrar también los pedidos de mostrador',
                ],
                'is_recommended' => true,
            ],
            4 => [
                'code' => 'P4',
                'name' => 'Delivery',
                'slug' => 'p4-delivery',
                'headline' => 'Controla tus propias entregas de principio a fin.',
                'description' => 'Cobras el envío según la distancia, le asignas el pedido a tu repartidor y tanto tú como el cliente ven por dónde va. Si tienes varios locales, el pedido sale del más cercano al cliente.',
                'icon' => 'map',
                'setup_price' => 1090,
                'monthly_price' => 189,
                'adds' => ['delivery-repartidores'],
                'bullets' => [
                    'Todo lo de Operación en local',
                    'El costo del envío se calcula según la distancia del cliente',
                    'El pedido sale del local más cercano a la dirección',
                    'Cada repartidor confirma la entrega desde su propio teléfono',
                    'Tú y tu cliente siguen el pedido hasta la puerta',
                ],
            ],
            5 => [
                'code' => 'P5',
                'name' => 'Omnicanal',
                'slug' => 'p5-omnicanal',
                'headline' => 'Todos tus canales de venta y todos tus cobros en un solo tablero.',
                'description' => 'Súmale las apps de delivery de terceros, los pedidos grandes de empresas y los cobros en línea. Todo entra al mismo lugar, aunque manejes varias marcas o varios locales.',
                'icon' => 'globe',
                'setup_price' => 1590,
                'monthly_price' => 249,
                'adds' => ['marketplaces', 'pedido-masivo', 'transferencias-bancarias', 'link-pago-tarjeta', 'franquicias-multimarca'],
                'bullets' => [
                    'Todo lo de Delivery',
                    'Los pedidos de PedidosYa, Uber Eats y Rappi en tu mismo tablero',
                    'Pedidos grandes para empresas y eventos, con su propio formulario',
                    'Cobro con tarjeta enviando un enlace de pago',
                    'Transferencias bancarias con comprobante adjunto',
                    'Varias marcas y varios locales desde una sola cuenta',
                ],
            ],
        ];

        $accumulated = [];

        foreach ($levels as $level => $package) {
            // Acumula los módulos de los niveles anteriores.
            $accumulated = array_values(array_unique([...$accumulated, ...$package['adds']]));

            PricingPlan::query()->updateOrCreate(
                ['slug' => $package['slug']],
                [
                    'name' => $package['name'],
                    'level' => $level,
                    'headline' => $package['headline'],
                    'description' => $package['description'],
                    'icon' => $package['icon'],
                    'setup_price' => $package['setup_price'],
                    'monthly_price' => $package['monthly_price'],
                    'currency' => 'USD',
                    'included' => $package['bullets'],
                    'included_item_slugs' => $accumulated,
                    'is_recommended' => $package['is_recommended'] ?? false,
                    'is_active' => true,
                    'sort_order' => $level * 10,
                ]
            );
        }
    }

    private function discounts(): void
    {
        // Plantilla inactiva: el equipo comercial define el valor y la activa.
        PricingDiscount::query()->updateOrCreate(
            ['name' => 'Descuento comercial autorizado'],
            [
                'description' => 'Plantilla de descuento. Defina el valor, las condiciones y actívelo desde el panel.',
                'type' => 'percentage',
                'value' => 0,
                'applies_to' => 'setup',
                'min_items' => 0,
                'is_automatic' => true,
                'is_active' => false,
                'sort_order' => 10,
            ]
        );

        PricingDiscount::query()->where('name', 'Descuento por configuración completa')->delete();
    }

    /** Un paquete ya cotizado se desactiva; el resto se borra. */
    private function retirePlans(): void
    {
        PricingPlan::query()
            ->whereIn('slug', self::OBSOLETE_PLANS)
            ->orWhere('slug', 'like', 'paquete-%')
            ->get()
            ->each(function (PricingPlan $plan) {
                if ($plan->quotations()->exists()) {
                    $plan->update(['is_active' => false]);

                    return;
                }

                $plan->delete();
            });
    }
}
