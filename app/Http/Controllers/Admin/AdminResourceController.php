<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaLibrary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Shared CRUD behaviour for the administration modules.
 *
 * Subclasses declare their model, validation rules and which attributes hold
 * uploaded files; everything else (listing, ordering, toggling, media
 * handling) is inherited.
 */
abstract class AdminResourceController extends Controller
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** @return array<string, mixed> */
    abstract protected function rules(Request $request, ?Model $record): array;

    /** Relations eager-loaded on listing. @var array<int, string> */
    protected array $with = [];

    /** Columns matched by the ?search= parameter. @var array<int, string> */
    protected array $searchable = [];

    /** Attributes that may receive an uploaded file. @var array<int, string> */
    protected array $uploads = [];

    /** Attributes cast to array before saving. @var array<int, string> */
    protected array $jsonFields = [];

    /** Attributes that accept a boolean toggle. @var array<int, string> */
    protected array $toggles = ['is_active'];

    protected string $mediaFolder = 'uploads';

    protected ?string $defaultSort = 'sort_order';

    public function index(Request $request): JsonResponse
    {
        $query = $this->model()::query()->with($this->with);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                foreach ($this->searchable as $i => $column) {
                    $i === 0
                        ? $q->where($column, 'like', "%{$search}%")
                        : $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $this->applyFilters($query, $request);

        if ($this->defaultSort) {
            $query->orderBy($this->defaultSort)->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        if ($request->filled('per_page')) {
            $perPage = min(100, max(5, (int) $request->query('per_page')));
            $page = $query->paginate($perPage);
            $page->getCollection()->each(fn (Model $record) => $this->decorate($record));

            return response()->json($page);
        }

        $records = $query->get();
        $records->each(fn (Model $record) => $this->decorate($record));

        return response()->json(['data' => $records]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->decorate($this->find($id)->load($this->with))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->prepare($request, $this->validated($request, null), null);

        $record = $this->model()::query()->create($data);

        $this->afterSave($request, $record);

        return response()->json([
            'message' => 'Registro creado.',
            'data' => $this->decorate($record->fresh($this->with)),
        ], HttpResponse::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->find($id);

        $data = $this->prepare($request, $this->validated($request, $record), $record);

        $record->update($data);

        $this->afterSave($request, $record);

        return response()->json([
            'message' => 'Cambios guardados.',
            'data' => $this->decorate($record->fresh($this->with)),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = $this->find($id);

        if (($record->is_locked ?? false) === true) {
            return response()->json([
                'message' => 'Este registro es parte de la estructura del sitio y no se puede eliminar. Puedes desactivarlo.',
            ], HttpResponse::HTTP_FORBIDDEN);
        }

        foreach ($this->uploads as $field) {
            $this->deleteStored($record->getAttribute($field));
        }

        $record->delete();

        return response()->json(['message' => 'Registro eliminado.']);
    }

    /** Persist a new manual ordering: { "order": [id, id, ...] }. */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer'],
        ]);

        foreach ($validated['order'] as $position => $id) {
            $this->model()::query()->whereKey($id)->update(['sort_order' => ($position + 1) * 10]);
        }

        return response()->json(['message' => 'Orden actualizado.']);
    }

    /** Flip a boolean column: { "field": "is_active", "value": true }. */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string'],
            'value' => ['required', 'boolean'],
        ]);

        if (! in_array($validated['field'], $this->toggles, true)) {
            return response()->json(['message' => 'Campo no permitido.'], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $record = $this->find($id);
        $record->update([$validated['field'] => $validated['value']]);

        return response()->json(['message' => 'Estado actualizado.', 'data' => $record]);
    }

    protected function find(int $id): Model
    {
        return $this->model()::query()->findOrFail($id);
    }

    /**
     * Validate the request against the resource rules plus the media-library
     * companions: every image field also accepts "<field>_path", the path of
     * an image that already exists in the library.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Model $record): array
    {
        $rules = $this->rules($request, $record);

        foreach ($this->uploads as $field) {
            $rules[$field.'_path'] = ['nullable', 'string', 'max:512', 'exists:media,path'];
        }

        return $request->validate($rules);
    }

    /**
     * Expose a resolved URL next to every stored media path, so the panel can
     * preview images regardless of where they live.
     */
    protected function decorate(Model $record): Model
    {
        foreach ($this->uploads as $field) {
            $record->setAttribute($field.'_url', media_url($record->getAttribute($field)));
        }

        return $record;
    }

    /** Hook for subclass-specific list filters. */
    protected function applyFilters(mixed $query, Request $request): void {}

    /** Hook called after create/update. */
    protected function afterSave(Request $request, Model $record): void {}

    /**
     * Normalise validated input: store uploads, encode JSON columns and honour
     * explicit "<field>_remove" flags.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        foreach ($this->uploads as $field) {
            $file = $request->file($field);

            if ($file instanceof UploadedFile) {
                $this->deleteStored($record?->getAttribute($field));
                $data[$field] = $this->storeFile($file);
                unset($data[$field.'_path']);

                continue;
            }

            // An image reused from the library: point at it without copying
            // the file, and leave the previous one in place for other uses.
            if (filled($data[$field.'_path'] ?? null)) {
                $data[$field] = $data[$field.'_path'];
                unset($data[$field.'_path']);

                continue;
            }

            unset($data[$field.'_path']);

            if ($request->boolean($field.'_remove')) {
                $this->deleteStored($record?->getAttribute($field));
                $data[$field] = null;

                continue;
            }

            // A multipart request serialises absent files as empty strings;
            // keep the stored value instead of wiping it.
            if (array_key_exists($field, $data) && $data[$field] === '') {
                unset($data[$field]);
            }
        }

        foreach ($this->jsonFields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $data[$field] = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
        }

        return $data;
    }

    /**
     * Store an upload through the media library, so every image uploaded from
     * any form appears in the catalogue and can be reused elsewhere.
     */
    protected function storeFile(UploadedFile $file): string
    {
        return app(MediaLibrary::class)
            ->store($file, $this->mediaFolder, request()->user()?->id)
            ->path;
    }

    /**
     * Forget a stored image.
     *
     * Files shipped in /public and absolute URLs are never touched, and an
     * image still referenced somewhere else keeps its file: the library is
     * shared, so removing it here only detaches it from this record.
     */
    protected function deleteStored(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '/', 'data:'])) {
            return;
        }

        if (app(MediaLibrary::class)->usage($path) !== []) {
            return;
        }

        Storage::disk('public')->delete($path);
        Media::query()->where('path', $path)->delete();
    }
}
