<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\BusinessType;
use App\Models\ContentModule;
use App\Models\ContentSection;
use App\Models\Demonstration;
use App\Models\Feature;
use App\Models\Media;
use App\Models\SeoSetting;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Single catalogue for every image of the site.
 *
 * Covers both the assets shipped inside /public (brand, hero art, demo covers)
 * and everything uploaded from the panel, so the administrator sees one list.
 */
class MediaLibrary
{
    public const FOLDERS = [
        'biblioteca' => 'Biblioteca',
        'marca' => 'Marca',
        'banners' => 'Banners',
        'secciones' => 'Secciones',
        'modulos' => 'Módulos',
        'funcionalidades' => 'Funcionalidades',
        'demos' => 'Demostraciones',
        'testimonios' => 'Testimonios',
        'tipos-negocio' => 'Tipos de negocio',
        'seo' => 'SEO',
        'ajustes' => 'Configuración',
    ];

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

    /**
     * Columns that may hold a media path, used to tell what is in use.
     *
     * @var array<class-string, array<string, string>>
     */
    private const USAGE_MAP = [
        Banner::class => ['image_desktop' => 'Banner · escritorio', 'image_mobile' => 'Banner · móvil'],
        ContentSection::class => ['image' => 'Sección'],
        ContentModule::class => ['image' => 'Módulo de contenido'],
        Feature::class => ['image' => 'Funcionalidad'],
        Demonstration::class => ['logo' => 'Demostración · logo', 'cover_image' => 'Demostración · portada'],
        Testimonial::class => ['avatar' => 'Testimonio'],
        BusinessType::class => ['image' => 'Tipo de negocio'],
        SeoSetting::class => ['og_image' => 'SEO · Open Graph'],
    ];

    /** Folder each upload path maps to, so legacy uploads land in the right bucket. */
    private const PATH_FOLDERS = [
        // Folders written by the panel today.
        'banners' => 'banners',
        'secciones' => 'secciones',
        'modulos' => 'modulos',
        'funcionalidades' => 'funcionalidades',
        'demos' => 'demos',
        'testimonios' => 'testimonios',
        'tipos-negocio' => 'tipos-negocio',
        'seo' => 'seo',
        'ajustes' => 'ajustes',
        'marca' => 'marca',
        'uploads' => 'biblioteca',
        // Earlier English folder names, kept so existing files stay classified.
        'sections' => 'secciones',
        'modules' => 'modulos',
        'features' => 'funcionalidades',
        'testimonials' => 'testimonios',
        'business-types' => 'tipos-negocio',
        'settings' => 'ajustes',
    ];

    /**
     * Reconcile the catalogue with what is actually on disk.
     *
     * @return array{registered:int, removed:int, total:int}
     */
    public function sync(): array
    {
        $found = collect()
            ->merge($this->scanShipped())
            ->merge($this->scanUploads())
            ->keyBy('path');

        $known = Media::query()->pluck('id', 'path');
        $registered = 0;

        foreach ($found as $path => $file) {
            if ($known->has($path)) {
                continue;
            }

            Media::query()->create($file);
            $registered++;
        }

        // Records whose file disappeared are dropped, so the picker never
        // offers a broken image.
        $removed = Media::query()
            ->whereNotIn('path', $found->keys())
            ->get()
            ->each(fn (Media $media) => $media->delete())
            ->count();

        return [
            'registered' => $registered,
            'removed' => $removed,
            'total' => Media::query()->count(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function scanShipped(): Collection
    {
        $root = public_path('images');

        if (! File::isDirectory($root)) {
            return collect();
        }

        return collect(File::allFiles($root))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), self::EXTENSIONS, true))
            ->map(function ($file) {
                $relative = str_replace('\\', '/', Str::after($file->getPathname(), public_path()));
                $path = '/'.ltrim($relative, '/');
                $folder = Str::contains($path, '/images/brand/') ? 'marca'
                    : (Str::contains($path, '/images/demos/') ? 'demos'
                        : (Str::contains($path, '/images/hero/') ? 'banners' : 'biblioteca'));

                return $this->describe($path, $file->getPathname(), $folder, 'shipped', true);
            })
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function scanUploads(): Collection
    {
        $disk = Storage::disk('public');

        return collect($disk->allFiles())
            ->filter(fn (string $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true))
            ->map(function (string $path) use ($disk) {
                $prefix = Str::before($path, '/');
                $folder = self::PATH_FOLDERS[$prefix] ?? 'biblioteca';

                return $this->describe($path, $disk->path($path), $folder, 'public', false);
            })
            ->values();
    }

    /** @return array<string, mixed> */
    private function describe(string $path, string $absolute, string $folder, string $source, bool $protected): array
    {
        $size = @filesize($absolute) ?: 0;
        $dimensions = $this->dimensions($absolute);

        return [
            'name' => pathinfo($path, PATHINFO_BASENAME),
            'path' => $path,
            'source' => $source,
            'folder' => $folder,
            'mime' => $this->mime($absolute, $path),
            'size' => $size,
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'is_protected' => $protected,
        ];
    }

    /** @return array{0:int|null, 1:int|null} */
    private function dimensions(string $absolute): array
    {
        if (strtolower(pathinfo($absolute, PATHINFO_EXTENSION)) === 'svg') {
            return $this->svgDimensions($absolute);
        }

        $info = @getimagesize($absolute);

        return $info ? [(int) $info[0], (int) $info[1]] : [null, null];
    }

    /** @return array{0:int|null, 1:int|null} */
    private function svgDimensions(string $absolute): array
    {
        $head = @file_get_contents($absolute, false, null, 0, 1024);

        if ($head === false) {
            return [null, null];
        }

        if (preg_match('/\bwidth="(\d+(?:\.\d+)?)"/', $head, $w)
            && preg_match('/\bheight="(\d+(?:\.\d+)?)"/', $head, $h)) {
            return [(int) $w[1], (int) $h[1]];
        }

        if (preg_match('/viewBox="[\d.\s-]*?([\d.]+)\s+([\d.]+)"/', $head, $box)) {
            return [(int) $box[1], (int) $box[2]];
        }

        return [null, null];
    }

    private function mime(string $absolute, string $path): ?string
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
            return 'image/svg+xml';
        }

        return @mime_content_type($absolute) ?: null;
    }

    /** Store an uploaded file and catalogue it. */
    public function store(UploadedFile $file, string $folder = 'biblioteca', ?int $userId = null): Media
    {
        $folder = array_key_exists($folder, self::FOLDERS) ? $folder : 'biblioteca';
        $name = Str::limit(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'imagen', 60, '');
        $directory = $folder === 'biblioteca' ? 'uploads' : $folder;

        $path = $file->storeAs(
            $directory,
            $name.'-'.Str::random(8).'.'.strtolower($file->getClientOriginalExtension()),
            'public'
        );

        $attributes = $this->describe($path, Storage::disk('public')->path($path), $folder, 'public', false);
        $attributes['name'] = $file->getClientOriginalName();
        $attributes['uploaded_by'] = $userId;

        return Media::query()->create($attributes);
    }

    /**
     * Where a given path is referenced across the site.
     *
     * @return array<int, array{type:string, id:int, label:string}>
     */
    public function usage(string $path): array
    {
        $uses = [];

        foreach (self::USAGE_MAP as $model => $columns) {
            foreach ($columns as $column => $label) {
                $model::query()
                    ->where($column, $path)
                    ->get()
                    ->each(function ($record) use (&$uses, $label) {
                        $uses[] = [
                            'type' => $label,
                            'id' => $record->getKey(),
                            'label' => $record->title
                                ?? $record->name
                                ?? $record->business_name
                                ?? $record->internal_name
                                ?? $record->author
                                ?? $record->page_key
                                ?? ('#'.$record->getKey()),
                        ];
                    });
            }
        }

        SiteSetting::query()
            ->where('type', 'image')
            ->where('value', $path)
            ->get()
            ->each(function (SiteSetting $setting) use (&$uses) {
                $uses[] = [
                    'type' => 'Configuración general',
                    'id' => $setting->id,
                    'label' => $setting->label ?? $setting->key,
                ];
            });

        Demonstration::query()
            ->whereNotNull('screenshots')
            ->get()
            ->each(function (Demonstration $demo) use (&$uses, $path) {
                $used = collect($demo->screenshots ?? [])->contains(
                    fn ($shot) => (is_array($shot) ? ($shot['url'] ?? null) : $shot) === $path
                );

                if ($used) {
                    $uses[] = [
                        'type' => 'Demostración · captura',
                        'id' => $demo->id,
                        'label' => $demo->business_name,
                    ];
                }
            });

        return $uses;
    }

    /** Replace every reference to one path with another. */
    public function replaceReferences(string $from, string $to): int
    {
        $changed = 0;

        foreach (self::USAGE_MAP as $model => $columns) {
            foreach (array_keys($columns) as $column) {
                $changed += $model::query()->where($column, $from)->update([$column => $to]);
            }
        }

        $changed += SiteSetting::query()
            ->where('type', 'image')
            ->where('value', $from)
            ->update(['value' => $to]);

        SiteSetting::flush();

        return $changed;
    }
}
