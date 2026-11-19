<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Tu restaurante merece su propia tienda online',
                'eyebrow' => 'Plataforma HIVEDFOOD',
                'description' => 'Recibe pedidos directamente desde tu propio canal digital y ofrece a tus clientes una experiencia de compra profesional.',
                'cta_text' => 'Solicitar demostración',
                'cta_url' => '#contacto',
                'cta_secondary_text' => 'Explorar la plataforma',
                'cta_secondary_url' => '#plataforma',
                'image_desktop' => null,
                'image_mobile' => null,
                'image_alt' => 'Tablero de pedidos y tienda de un restaurante en el móvil',
                'theme' => 'dark',
                'align' => 'left',
                'transition' => 'fade',
                'duration' => 7000,
                'sort_order' => 1,
            ],
            [
                'title' => 'Tu marca. Tu tienda. Tus clientes.',
                'eyebrow' => 'Identidad propia',
                'description' => 'Personaliza tu tienda digital con los colores, productos y estilo de tu negocio.',
                'cta_text' => 'Ver demostración',
                'cta_url' => '#demostraciones',
                'cta_secondary_text' => 'Explorar la plataforma',
                'cta_secondary_url' => '#plataforma',
                'image_desktop' => null,
                'image_mobile' => null,
                'image_alt' => 'Tienda personalizada con la identidad visual del negocio',
                'theme' => 'dark',
                'align' => 'left',
                'transition' => 'slide',
                'duration' => 7000,
                'sort_order' => 2,
            ],
            [
                'title' => 'Todo tu negocio conectado en un solo lugar',
                'eyebrow' => 'Operación simple',
                'description' => 'Administra tus productos y pedidos desde una plataforma diseñada para simplificar tu operación.',
                'cta_text' => 'Solicitar demostración',
                'cta_url' => '#contacto',
                'cta_secondary_text' => 'Cotizar mi solución',
                'cta_secondary_url' => '#cotizador',
                'image_desktop' => null,
                'image_mobile' => null,
                'image_alt' => 'Panel administrativo con los pedidos del día',
                'theme' => 'dark',
                'align' => 'left',
                'transition' => 'zoom',
                'duration' => 7000,
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::query()->updateOrCreate(
                ['title' => $banner['title']],
                $banner + ['is_active' => true]
            );
        }
    }
}
