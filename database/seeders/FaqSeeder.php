<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => '¿HIVEDFOOD es un marketplace?',
                'answer' => 'No. Cada negocio recibe sus propios canales de venta, con su marca y su catálogo. No compartes vitrina con otros restaurantes ni compites por posición dentro de una aplicación de terceros.',
                'sort_order' => 10,
            ],
            [
                'question' => '¿Necesito un número de WhatsApp nuevo?',
                'answer' => 'Puedes usar el número que ya atiende a tus clientes o uno nuevo. Durante la implementación lo conectamos a la plataforma, y un mismo negocio puede manejar varios números si lo necesita.',
                'sort_order' => 20,
            ],
            [
                'question' => '¿Los pedidos de PedidosYa, Uber Eats o Rappi también entran aquí?',
                'answer' => 'Sí. Con el módulo de marketplaces, esos pedidos llegan al mismo tablero que los de WhatsApp o tu tienda, con la misma numeración y la misma comanda para la cocina. Tu equipo deja de saltar entre tablets.',
                'sort_order' => 30,
            ],
            [
                'question' => '¿Puedo empezar solo con WhatsApp y crecer después?',
                'answer' => 'Sí, es lo más habitual. Se empieza por el paquete de entrada con el chatbot y la carta, y luego se suman canales, sucursales o integraciones conforme la operación lo pide. Cada módulo se activa sin rehacer lo anterior.',
                'sort_order' => 40,
            ],
            [
                'question' => '¿Qué pasa si el cliente paga por transferencia?',
                'answer' => 'El cliente envía su comprobante por WhatsApp o desde la web, y tu equipo lo verifica contra el banco antes de que el pedido salga a preparación. Los comprobantes quedan archivados para contabilidad.',
                'sort_order' => 50,
            ],
            [
                'question' => '¿La plataforma emite facturas?',
                'answer' => 'Sí. Con el módulo de facturación electrónica, integrado con Contífico, la factura se emite desde el propio detalle del pedido, sin volver a escribir los datos. Las credenciales se configuran por empresa y por sucursal.',
                'sort_order' => 60,
            ],
            [
                'question' => '¿Sirve si tengo varias sucursales?',
                'answer' => 'Sí. Cada sucursal tiene su horario, su catálogo, sus zonas de entrega y su equipo, y los reportes te permiten comparar entre locales. Si manejas varias marcas, también pueden convivir en la misma cuenta de forma independiente.',
                'sort_order' => 70,
            ],
            [
                'question' => '¿Puedo administrar la carta y el bot por mi cuenta?',
                'answer' => 'Sí. El catálogo, los precios, las promociones y hasta el guion de la conversación del bot se administran desde el panel. Durante la implementación capacitamos a tu equipo para que pueda hacerlo sin depender de nosotros.',
                'sort_order' => 80,
            ],
            [
                'question' => '¿Cuánto demora la implementación?',
                'answer' => 'Depende del tamaño de tu carta, de cuántos canales quieras abrir y de qué tan listo esté tu contenido. En la reunión de levantamiento definimos un cronograma concreto según tu caso.',
                'sort_order' => 90,
            ],
            [
                'question' => '¿Qué necesito tener listo para empezar?',
                'answer' => 'El logotipo de tu negocio, la lista de productos con sus precios y opciones, las fotografías que quieras mostrar y el número de WhatsApp que vas a usar. Si no tienes todo listo, lo revisamos juntos durante la implementación.',
                'sort_order' => 100,
            ],
            [
                'question' => '¿El cotizador me da el precio final?',
                'answer' => 'El cotizador arma tu configuración y una estimación a partir de las tarifas registradas. La propuesta definitiva se confirma con el equipo comercial después de revisar tu caso.',
                'sort_order' => 110,
            ],
            [
                'question' => '¿Cómo solicito una demostración?',
                'answer' => 'Puedes completar el formulario de esta página o escribirnos por WhatsApp. Coordinamos una sesión y te mostramos un pedido real entrando por WhatsApp, pasando por cocina y saliendo a reparto.',
                'sort_order' => 120,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->updateOrCreate(['question' => $faq['question']], $faq);
        }

        // Preguntas de la versión anterior que quedaron reemplazadas.
        Faq::query()->whereIn('question', [
            '¿Puedo administrar mi catálogo por mi cuenta?',
            '¿La tienda funciona bien desde el celular?',
            '¿Puedo crecer después con más funcionalidades?',
        ])->delete();
    }
}
