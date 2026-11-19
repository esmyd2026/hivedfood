<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MediaController extends Controller
{
    public function __construct(private MediaLibrary $library) {}

    public function index(Request $request): JsonResponse
    {
        // First visit of a fresh install: catalogue what is already on disk.
        if (! Media::query()->exists()) {
            $this->library->sync();
        }

        $query = Media::query()->ordered();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('folder')) {
            $query->where('folder', $request->query('folder'));
        }

        $page = $query->paginate(min(96, max(12, (int) $request->query('per_page', 24))));

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
            'folders' => MediaLibrary::FOLDERS,
        ]);
    }

    public function show(Media $media): JsonResponse
    {
        return response()->json([
            'data' => $media,
            'usage' => $this->library->usage($media->path),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg,avif', 'max:6144'],
            'folder' => ['nullable', Rule::in(array_keys(MediaLibrary::FOLDERS))],
            'alt' => ['nullable', 'string', 'max:200'],
        ]);

        $created = collect($validated['files'])->map(function ($file) use ($validated, $request) {
            $media = $this->library->store($file, $validated['folder'] ?? 'biblioteca', $request->user()?->id);

            if (filled($validated['alt'] ?? null)) {
                $media->update(['alt' => $validated['alt']]);
            }

            return $media->fresh();
        });

        return response()->json([
            'message' => $created->count() === 1
                ? 'Imagen subida.'
                : $created->count().' imágenes subidas.',
            'data' => $created->values(),
        ], HttpResponse::HTTP_CREATED);
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'alt' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'folder' => ['sometimes', Rule::in(array_keys(MediaLibrary::FOLDERS))],
        ]);

        $media->update($validated);

        return response()->json(['message' => 'Imagen actualizada.', 'data' => $media->fresh()]);
    }

    /**
     * Replace the file behind an image, keeping every reference intact.
     *
     * This is what makes "cambiar una imagen del sitio" a single step: the
     * records that point at the old path are repointed at the new one.
     */
    public function replace(Request $request, Media $media): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg,avif', 'max:6144'],
        ]);

        $replacement = $this->library->store(
            $request->file('file'),
            $media->folder,
            $request->user()?->id
        );

        $replacement->update([
            'alt' => $media->alt,
            'description' => $media->description,
        ]);

        $references = $this->library->replaceReferences($media->path, $replacement->path);

        if (! $media->isShipped()) {
            $media->deleteFile();
            $media->delete();
        }

        return response()->json([
            'message' => $references > 0
                ? "Imagen reemplazada en {$references} lugar(es)."
                : 'Imagen reemplazada.',
            'data' => $replacement->fresh(),
        ]);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        if ($media->is_protected || $media->isShipped()) {
            return response()->json([
                'message' => 'Esta imagen forma parte de los recursos del proyecto. Puedes reemplazarla, pero no eliminarla.',
            ], HttpResponse::HTTP_FORBIDDEN);
        }

        $usage = $this->library->usage($media->path);

        if ($usage !== [] && ! $request->boolean('force')) {
            return response()->json([
                'message' => 'La imagen está en uso en '.count($usage).' lugar(es).',
                'usage' => $usage,
            ], HttpResponse::HTTP_CONFLICT);
        }

        $media->deleteFile();
        $media->delete();

        return response()->json(['message' => 'Imagen eliminada.']);
    }

    public function sync(): JsonResponse
    {
        $result = $this->library->sync();

        return response()->json([
            'message' => "Catálogo actualizado: {$result['registered']} nuevas, {$result['removed']} retiradas, {$result['total']} en total.",
            'data' => $result,
        ]);
    }
}
