<?php

namespace Database\Seeders;

use App\Models\Demonstration;
use Illuminate\Database\Seeder;

/**
 * Real implementations of the platform.
 *
 * Demo URLs are intentionally left empty: they must be filled in from the
 * admin panel with the authorised addresses. While a URL is missing the public
 * card falls back to a "request access" action instead of a dead link.
 */
class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        $demos = [
            [
                'business_name' => 'Dpikeos',
                'slug' => 'dpikeos',
                'category' => 'Restaurante',
                'description' => 'Implementación de la plataforma HIVEDFOOD con catálogo, contenido y tienda digital propia.',
                'highlights' => ['Tienda digital', 'Catálogo administrable', 'Identidad propia'],
                'accent_color' => '#E96D42',
                'cover_image' => '/images/demos/demo-dpikeos.svg',
                'is_featured' => true,
                'sort_order' => 10,
            ],
            [
                'business_name' => 'Gonzales',
                'slug' => 'gonzales',
                'category' => 'Comida rápida',
                'description' => 'Tienda en línea configurada con la identidad del negocio y su estructura de productos.',
                'highlights' => ['Catálogo por categorías', 'Banners promocionales', 'Experiencia móvil'],
                'accent_color' => '#F4B83F',
                'cover_image' => '/images/demos/demo-gonzales.svg',
                'sort_order' => 20,
            ],
            [
                'business_name' => 'Torki',
                'slug' => 'torki',
                'category' => 'Gastronomía',
                'description' => 'Canal digital propio con gestión de contenido y pedidos desde el panel administrativo.',
                'highlights' => ['Gestión de pedidos', 'Contenido administrable', 'Marca propia'],
                'accent_color' => '#202A24',
                'cover_image' => '/images/demos/demo-torki.svg',
                'sort_order' => 30,
            ],
        ];

        foreach ($demos as $demo) {
            Demonstration::query()->updateOrCreate(
                ['slug' => $demo['slug']],
                $demo + ['is_published' => true]
            );
        }
    }
}
