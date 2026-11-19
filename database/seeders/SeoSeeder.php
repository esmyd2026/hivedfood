<?php

namespace Database\Seeders;

use App\Models\SeoSetting;
use Illuminate\Database\Seeder;

class SeoSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'page_key' => 'home',
                'path' => '/',
                'name' => 'Inicio',
                'meta_title' => 'HIVEDFOOD | Pedidos por WhatsApp y tienda online para restaurantes',
                'meta_description' => 'Plataforma de pedidos para restaurantes y comida rápida: chatbot de WhatsApp, tienda online, kiosco, mostrador y marketplaces en un solo tablero, con cocina, delivery y facturación. Solicita una demostración.',
                'meta_keywords' => 'pedidos por whatsapp para restaurantes, chatbot de whatsapp para restaurantes, sistema de pedidos online, tienda online para restaurantes, software para comida rápida, plataforma de delivery propia, software para restaurantes, punto de venta para restaurantes',
                'og_title' => 'HIVEDFOOD | Todos tus pedidos en un solo tablero',
                'og_description' => 'WhatsApp, tienda en línea, kiosco, mostrador y marketplaces entran al mismo pedido, con la misma cocina y los mismos reportes.',
                'sitemap_priority' => 1.0,
                'sitemap_changefreq' => 'weekly',
            ],
            [
                'page_key' => 'demos',
                'path' => '/demostraciones',
                'name' => 'Demostraciones',
                'meta_title' => 'Demostraciones | Tiendas online creadas con HIVEDFOOD',
                'meta_description' => 'Conoce implementaciones reales de la plataforma HIVEDFOOD en restaurantes y negocios gastronómicos.',
                'sitemap_priority' => 0.8,
                'sitemap_changefreq' => 'monthly',
            ],
            [
                'page_key' => 'quoter',
                'path' => '/cotizador',
                'name' => 'Cotizador',
                'meta_title' => 'Cotizador | Arma la plataforma de pedidos de tu negocio',
                'meta_description' => 'Elige tu tipo de negocio, tu paquete y los módulos que necesitas. Recibe una propuesta a la medida de tu operación gastronómica.',
                'sitemap_priority' => 0.9,
                'sitemap_changefreq' => 'monthly',
            ],
        ];

        foreach ($pages as $page) {
            SeoSetting::query()->updateOrCreate(['page_key' => $page['page_key']], $page);
        }
    }
}
