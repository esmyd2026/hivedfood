# HIVEDFOOD · Sitio comercial

Sitio web comercial y plataforma de captación de **HIVEDFOOD**, la solución SaaS de comercio
electrónico para restaurantes, locales de comida rápida, cafeterías y negocios gastronómicos.

Construido con **Laravel 13 + Vue 3**. Este repositorio contiene únicamente el sitio de promoción,
demostración y captación: no modifica ni depende de la plataforma SaaS en producción.

## Qué incluye

- **Portada administrable** de 15 secciones con hero de banners dinámicos.
- **Cotizador interactivo** de 5 pasos con cálculo de precios en el backend, guardado, PDF y
  envío por correo.
- **Panel administrativo** completo: biblioteca de imágenes, banners, secciones, módulos,
  funcionalidades, demostraciones, testimonios, FAQ, tarifas, leads, cotizaciones, SEO y
  configuración general.
- **Todo administrable**: imágenes, textos, números y colores se editan desde el panel; cambiar un
  color o reemplazar una imagen se refleja en el sitio al instante, sin recompilar.
- **SEO técnico**: contenido renderizado en servidor, metadatos administrables, sitemap, robots,
  Open Graph y datos estructurados.
- **Captación**: WhatsApp flotante, formularios validados en backend y protección antispam.

## Puesta en marcha

```sh
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

El panel queda en `/admin`. El seeder crea el administrador inicial y muestra su contraseña una
sola vez (o usa la de `ADMIN_PASSWORD` en `.env`).

```sh
php artisan test        # 57 pruebas
./vendor/bin/pint       # estilo de código
```

## Documentación

**[docs/HIVEDFOOD.md](docs/HIVEDFOOD.md)** — arquitectura, decisión de renderizado, guía del panel,
funcionamiento del cotizador, SEO, antispam y **qué queda pendiente de datos reales** (tarifas,
URL de las demostraciones y testimonios).

---

Framework: [Laravel](https://laravel.com/docs) · [Vue](https://vuejs.org) · [Tailwind CSS](https://tailwindcss.com)
