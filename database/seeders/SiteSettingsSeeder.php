<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name', 'group' => 'general', 'type' => 'string', 'label' => 'Nombre del sitio', 'value' => 'HIVEDFOOD'],
            ['key' => 'legal_name', 'group' => 'general', 'type' => 'string', 'label' => 'Razón social', 'value' => 'HIVEDFOOD'],
            ['key' => 'tagline', 'group' => 'general', 'type' => 'text', 'label' => 'Tagline', 'value' => 'La plataforma de comercio electrónico que le da a cada restaurante su propia tienda online.'],
            ['key' => 'logo', 'group' => 'general', 'type' => 'image', 'label' => 'Logotipo', 'value' => '/images/brand/hivedfood-logo.svg'],
            ['key' => 'logo_light', 'group' => 'general', 'type' => 'image', 'label' => 'Logotipo claro', 'value' => '/images/brand/hivedfood-logo-light.svg'],
            ['key' => 'favicon', 'group' => 'general', 'type' => 'image', 'label' => 'Favicon', 'value' => '/images/brand/favicon.svg'],
            ['key' => 'og_image', 'group' => 'general', 'type' => 'image', 'label' => 'Imagen para compartir', 'value' => '/images/brand/og-cover.svg'],

            // Contacto
            ['key' => 'email', 'group' => 'contact', 'type' => 'string', 'label' => 'Correo comercial', 'value' => ''],
            ['key' => 'phone', 'group' => 'contact', 'type' => 'string', 'label' => 'Teléfono', 'value' => ''],
            ['key' => 'whatsapp_number', 'group' => 'contact', 'type' => 'string', 'label' => 'WhatsApp (solo números con código de país)', 'value' => ''],
            ['key' => 'whatsapp_message', 'group' => 'contact', 'type' => 'text', 'label' => 'Mensaje inicial de WhatsApp', 'value' => 'Hola, quiero conocer HIVEDFOOD para mi negocio.'],
            ['key' => 'address', 'group' => 'contact', 'type' => 'string', 'label' => 'Dirección', 'value' => ''],
            ['key' => 'city', 'group' => 'contact', 'type' => 'string', 'label' => 'Ciudad', 'value' => ''],
            ['key' => 'country', 'group' => 'contact', 'type' => 'string', 'label' => 'País', 'value' => ''],
            ['key' => 'schedule', 'group' => 'contact', 'type' => 'string', 'label' => 'Horario de atención', 'value' => 'Lunes a viernes, 9:00 – 18:00'],
            ['key' => 'demo_calendar_url', 'group' => 'contact', 'type' => 'string', 'label' => 'Enlace de agenda para demos', 'value' => ''],
            ['key' => 'notification_email', 'group' => 'contact', 'type' => 'string', 'label' => 'Correo que recibe los leads', 'value' => ''],

            // Redes
            ['key' => 'facebook_url', 'group' => 'social', 'type' => 'string', 'label' => 'Facebook', 'value' => ''],
            ['key' => 'instagram_url', 'group' => 'social', 'type' => 'string', 'label' => 'Instagram', 'value' => ''],
            ['key' => 'linkedin_url', 'group' => 'social', 'type' => 'string', 'label' => 'LinkedIn', 'value' => ''],
            ['key' => 'tiktok_url', 'group' => 'social', 'type' => 'string', 'label' => 'TikTok', 'value' => ''],
            ['key' => 'youtube_url', 'group' => 'social', 'type' => 'string', 'label' => 'YouTube', 'value' => ''],

            // Identidad visual
            ['key' => 'color_charcoal', 'group' => 'brand', 'type' => 'color', 'label' => 'Carbón', 'value' => '#202A24'],
            ['key' => 'color_honey', 'group' => 'brand', 'type' => 'color', 'label' => 'Amarillo miel', 'value' => '#F4B83F'],
            ['key' => 'color_ivory', 'group' => 'brand', 'type' => 'color', 'label' => 'Marfil', 'value' => '#F7F5EF'],
            ['key' => 'color_coral', 'group' => 'brand', 'type' => 'color', 'label' => 'Coral', 'value' => '#E96D42'],

            // Cotizador
            ['key' => 'pricing_published', 'group' => 'pricing', 'type' => 'boolean', 'label' => 'Publicar precios en el cotizador', 'value' => '1'],
            ['key' => 'pricing_currency', 'group' => 'pricing', 'type' => 'string', 'label' => 'Moneda', 'value' => 'USD'],
            ['key' => 'pricing_currency_symbol', 'group' => 'pricing', 'type' => 'string', 'label' => 'Símbolo de moneda', 'value' => '$'],
            ['key' => 'quotation_validity_days', 'group' => 'pricing', 'type' => 'string', 'label' => 'Vigencia de la cotización (días)', 'value' => '15'],
            [
                'key' => 'pricing_unpriced_message',
                'group' => 'pricing',
                'type' => 'text',
                'label' => 'Mensaje cuando no hay precios publicados',
                'value' => 'Aún no publicamos tarifas en línea. Nuestro equipo comercial revisará tu configuración y te enviará una propuesta con los precios finales.',
            ],
            ['key' => 'quotation_projection_months', 'group' => 'pricing', 'type' => 'string', 'label' => 'Meses de la proyección anual', 'value' => '12'],
            ['key' => 'quotation_tax_percent', 'group' => 'pricing', 'type' => 'string', 'label' => 'Impuesto aplicable (%)', 'value' => ''],
            [
                'key' => 'module_categories',
                'group' => 'pricing',
                'type' => 'json',
                'label' => 'Categorías de módulos del cotizador',
                'value' => json_encode([
                    'canales' => 'Canales de venta',
                    'operacion' => 'Operación',
                    'integraciones' => 'Integraciones',
                    'automatizacion' => 'Automatización e inteligencia artificial',
                    'rrhh' => 'Recursos humanos',
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'external_costs',
                'group' => 'pricing',
                'type' => 'json',
                'label' => 'Servicios externos y costos no incluidos',
                'value' => json_encode([
                    [
                        'icon' => 'chat',
                        'title' => 'Meta · WhatsApp Business Platform',
                        'description' => 'El consumo de conversaciones y los cargos aplicables de Meta se facturan por separado, según el uso real.',
                    ],
                    [
                        'icon' => 'spark',
                        'title' => 'Inteligencia artificial',
                        'description' => 'El consumo del asistente se mide por uso y se factura según consumo.',
                    ],
                    [
                        'icon' => 'building',
                        'title' => 'Infraestructura del establecimiento',
                        'description' => 'Dominio propio cuando corresponda, tablets, impresoras, televisores, computadores e internet del local.',
                    ],
                    [
                        'icon' => 'layers',
                        'title' => 'Integraciones y desarrollos a medida',
                        'description' => 'Cualquier integración fuera del catálogo, desarrollos personalizados y servicios de terceros se cotizan aparte.',
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'quotation_conditions',
                'group' => 'pricing',
                'type' => 'text',
                'label' => 'Condiciones comerciales',
                'value' => implode("\n", [
                    'Los valores presentados son una estimación basada en la configuración seleccionada.',
                    'La proyección del primer año no incluye impuestos ni cargos externos variables.',
                    'El consumo de Meta y del asistente con IA se factura por separado, según el uso real.',
                    'La propuesta definitiva se confirma tras una reunión de levantamiento de requerimientos.',
                    'La implementación inicia una vez recibidos los contenidos del negocio.',
                ]),
            ],

            // Textos de la interfaz del sitio
            ['key' => 'hero_badge_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Hero · etiqueta flotante (título)', 'value' => 'Hivedfood'],
            ['key' => 'hero_badge_subtitle', 'group' => 'textos', 'type' => 'string', 'label' => 'Hero · etiqueta flotante (subtítulo)', 'value' => 'Plataforma en producción'],
            ['key' => 'about_showcase_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Presentación · título de la ilustración', 'value' => 'Una plataforma, muchas tiendas'],
            ['key' => 'about_showcase_note', 'group' => 'textos', 'type' => 'text', 'label' => 'Presentación · nota de la ilustración', 'value' => 'Cada negocio conserva su identidad visual sobre la misma base tecnológica.'],
            ['key' => 'about_showcase_badge', 'group' => 'textos', 'type' => 'string', 'label' => 'Presentación · etiqueta de estado', 'value' => 'Activa'],

            ['key' => 'preview_brand_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Vista previa · nombre de la tienda', 'value' => 'Tu marca'],
            ['key' => 'preview_banner_eyebrow', 'group' => 'textos', 'type' => 'string', 'label' => 'Vista previa · antetítulo del banner', 'value' => 'Promoción'],
            ['key' => 'preview_banner_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Vista previa · título del banner', 'value' => 'Tu banner, tu mensaje'],
            ['key' => 'preview_cta_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Vista previa · botón de la tienda', 'value' => 'Hacer pedido'],

            ['key' => 'cta_form_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Contacto · título del formulario', 'value' => 'Solicita tu demostración'],
            ['key' => 'cta_form_note', 'group' => 'textos', 'type' => 'string', 'label' => 'Contacto · nota del formulario', 'value' => 'Respondemos en horario comercial.'],
            ['key' => 'cta_direct_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Contacto · título de contacto directo', 'value' => '¿Prefieres escribirnos directamente?'],
            ['key' => 'cta_submit_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Contacto · botón de envío', 'value' => 'Enviar solicitud'],
            ['key' => 'cta_whatsapp_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Contacto · botón de WhatsApp', 'value' => 'Prefiero WhatsApp'],
            [
                'key' => 'cta_perks',
                'group' => 'textos',
                'type' => 'json',
                'label' => 'Contacto · ventajas de la demostración',
                'value' => json_encode([
                    ['icon' => 'clock', 'label' => 'Sesión de 30 minutos'],
                    ['icon' => 'shield', 'label' => 'Sin compromiso'],
                    ['icon' => 'users', 'label' => 'Con un caso parecido al tuyo'],
                ], JSON_UNESCAPED_UNICODE),
            ],

            ['key' => 'quoter_business_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 1, título', 'value' => '¿Qué tipo de negocio tienes?'],
            ['key' => 'quoter_business_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 1, descripción', 'value' => 'Adaptamos la configuración a tu formato de venta.'],
            ['key' => 'quoter_solution_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 2, título', 'value' => '¿Qué solución necesitas?'],
            ['key' => 'quoter_solution_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 2, descripción', 'value' => 'Puedes empezar por lo esencial y crecer después.'],
            ['key' => 'quoter_customization_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 3, título', 'value' => '¿Qué incluimos en la puesta en marcha?'],
            ['key' => 'quoter_customization_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 3, descripción', 'value' => 'Selecciona los servicios de configuración que quieres que realicemos por ti.'],
            ['key' => 'quoter_addons_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 4, título', 'value' => '¿Quieres sumar algún servicio adicional?'],
            ['key' => 'quoter_addons_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 4, descripción', 'value' => 'Opcional. Puedes contratarlos ahora o más adelante, cuando tu operación lo necesite.'],
            ['key' => 'quoter_notes_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · etiqueta del campo de notas', 'value' => '¿Algo más que debamos saber?'],
            ['key' => 'quoter_contact_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 5, título del formulario', 'value' => '¿A dónde enviamos tu cotización?'],
            ['key' => 'quoter_contact_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paso 5, descripción', 'value' => 'Guardamos tu configuración y nuestro equipo comercial la revisa contigo.'],
            ['key' => 'quoter_price_placeholder', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · texto cuando no hay precio', 'value' => 'Precio según configuración'],

            ['key' => 'form_question_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Formulario · pregunta abierta', 'value' => '¿Qué necesitas resolver?'],
            ['key' => 'form_consent_label', 'group' => 'textos', 'type' => 'text', 'label' => 'Formulario · texto de consentimiento', 'value' => 'Quiero recibir novedades y material comercial de HIVEDFOOD.'],
            [
                'key' => 'quoter_step_names',
                'group' => 'textos',
                'type' => 'json',
                'label' => 'Cotizador · nombres de los pasos',
                // Van por clave de paso para que el orden no importe.
                'value' => json_encode([
                    'business' => 'Tu negocio',
                    'package' => 'Paquete',
                    'billing' => 'Cómo pagas',
                    'modules' => 'Módulos',
                    'scale' => 'Operación',
                    'result' => 'Resumen',
                ], JSON_UNESCAPED_UNICODE),
            ],
            ['key' => 'quoter_package_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paquete, título', 'value' => 'Elige tu punto de partida'],
            ['key' => 'quoter_package_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · paquete, descripción', 'value' => 'Cada paquete incluye todo lo del anterior. Puedes empezar por lo esencial y ampliar cuando quieras.'],
            ['key' => 'quoter_billing_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · forma de pago, título', 'value' => '¿Cómo prefieres pagarlo?'],
            ['key' => 'quoter_billing_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · forma de pago, descripción', 'value' => 'La implementación y la mensualidad son las mismas. Lo único que cambia es si además pagas por los pedidos que procesas.'],
            ['key' => 'quoter_modules_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · módulos, título', 'value' => 'Añade lo que tu operación necesite'],
            ['key' => 'quoter_modules_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · módulos, descripción', 'value' => 'Lo que ya viene en tu paquete aparece marcado como incluido y no se cobra dos veces.'],
            ['key' => 'quoter_scale_title', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · escalabilidad, título', 'value' => 'Cuéntanos el tamaño de tu operación'],
            ['key' => 'quoter_scale_description', 'group' => 'textos', 'type' => 'string', 'label' => 'Cotizador · escalabilidad, descripción', 'value' => 'Locales, marcas y números de WhatsApp conectados. El primero de cada uno va incluido.'],

            ['key' => 'demo_link_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Demostraciones · botón con enlace', 'value' => 'Ver tienda'],
            ['key' => 'demo_request_label', 'group' => 'textos', 'type' => 'string', 'label' => 'Demostraciones · botón sin enlace', 'value' => 'Solicitar acceso a la demo'],

            ['key' => 'whatsapp_float_label', 'group' => 'textos', 'type' => 'string', 'label' => 'WhatsApp flotante · texto del botón', 'value' => 'Hablemos por WhatsApp'],
            ['key' => 'footer_tagline', 'group' => 'textos', 'type' => 'string', 'label' => 'Footer · línea inferior derecha', 'value' => 'Plataforma de comercio electrónico para negocios gastronómicos.'],

            // Paletas de la vista previa de tiendas (seccion Personalizacion)
            [
                'key' => 'preview_palettes',
                'group' => 'brand',
                'type' => 'json',
                'label' => 'Paletas de la vista previa de tiendas',
                'value' => json_encode([
                    ['name' => 'Miel', 'bg' => '#F7F5EF', 'accent' => '#F4B83F', 'ink' => '#202A24'],
                    ['name' => 'Coral', 'bg' => '#FFF6F2', 'accent' => '#E96D42', 'ink' => '#2B1C16'],
                    ['name' => 'Carbón', 'bg' => '#202A24', 'accent' => '#F4B83F', 'ink' => '#F7F5EF'],
                    ['name' => 'Marfil', 'bg' => '#FFFFFF', 'accent' => '#41544A', 'ink' => '#202A24'],
                ], JSON_UNESCAPED_UNICODE),
            ],

            // Footer y navegación
            ['key' => 'footer_text', 'group' => 'general', 'type' => 'text', 'label' => 'Texto del footer', 'value' => 'HIVEDFOOD es la plataforma que convierte tu restaurante en un canal digital propio: tienda, catálogo, pedidos y contenido, administrados desde un solo lugar.'],
            [
                'key' => 'nav_links',
                'group' => 'navigation',
                'type' => 'json',
                'label' => 'Enlaces del menú',
                'value' => json_encode([
                    ['label' => 'Solución', 'url' => '#plataforma'],
                    ['label' => 'Funcionalidades', 'url' => '#funcionalidades'],
                    ['label' => 'Demostraciones', 'url' => '#demostraciones'],
                    ['label' => 'Precios', 'url' => '#precios'],
                    ['label' => 'Preguntas frecuentes', 'url' => '#preguntas'],
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'footer_links',
                'group' => 'navigation',
                'type' => 'json',
                'label' => 'Enlaces del footer',
                'value' => json_encode([
                    [
                        'title' => 'Plataforma',
                        'links' => [
                            ['label' => 'Qué es HIVEDFOOD', 'url' => '#plataforma'],
                            ['label' => 'Funcionalidades', 'url' => '#funcionalidades'],
                            ['label' => 'Personalización', 'url' => '#personalizacion'],
                            ['label' => 'Tipos de negocio', 'url' => '#negocios'],
                        ],
                    ],
                    [
                        'title' => 'Comercial',
                        'links' => [
                            ['label' => 'Demostraciones', 'url' => '#demostraciones'],
                            ['label' => 'Cotizador', 'url' => '#cotizador'],
                            ['label' => 'Solicitar demostración', 'url' => '#contacto'],
                            ['label' => 'Preguntas frecuentes', 'url' => '#preguntas'],
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                    'value' => $setting['value'],
                ]
            );
        }

        $this->retired();

        SiteSetting::flush();
    }

    /**
     * Textos de pasos del cotizador que ya no existen.
     *
     * Se borran para que no aparezcan en el panel como ajustes vivos: el
     * cotizador dejó de preguntar por la forma de cobro y por el volumen de
     * pedidos cuando la monetización pasó a ser por módulos y sucursales.
     */
    private function retired(): void
    {
        SiteSetting::query()
            ->whereIn('key', [
                'quoter_usage_title',
                'quoter_usage_description',
            ])
            ->delete();
    }
}
