<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function __construct(private MediaLibrary $library) {}

    public function index(): JsonResponse
    {
        $settings = SiteSetting::query()
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->map(fn (SiteSetting $setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'group' => $setting->group,
                'type' => $setting->type,
                'label' => $setting->label,
                'value' => $setting->value,
                'preview' => $setting->type === 'image' ? media_url($setting->value) : null,
            ]);

        return response()->json([
            'data' => $settings->groupBy('group'),
        ]);
    }

    /**
     * Save a batch of settings.
     *
     * Values arrive as a flat key => value map. Image settings are uploaded as
     * files named "file__<key>".
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'values' => ['nullable', 'array'],
            'values.*' => ['nullable'],
        ]);

        $known = SiteSetting::query()->get()->keyBy('key');

        foreach ((array) $request->input('values', []) as $key => $value) {
            $setting = $known->get($key);

            if (! $setting) {
                continue;
            }

            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            if ($setting->type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0';
            }

            $setting->update(['value' => $value]);
        }

        foreach ($request->allFiles() as $field => $file) {
            if (! Str::startsWith($field, 'file__') || ! $file instanceof UploadedFile) {
                continue;
            }

            $key = Str::after($field, 'file__');
            $setting = $known->get($key);

            if (! $setting || $setting->type !== 'image') {
                continue;
            }

            $request->validate([$field => ['file', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg,avif', 'max:4096']]);

            $previous = $setting->value;

            // Uploads here join the shared library like any other image.
            $media = $this->library->store($file, 'ajustes', $request->user()?->id);
            $setting->update(['value' => $media->path]);

            $this->deleteStored($previous);
        }

        SiteSetting::flush();

        return response()->json(['message' => 'Configuración guardada.']);
    }

    /** An image still used elsewhere keeps its file: the library is shared. */
    private function deleteStored(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '/', 'data:'])) {
            return;
        }

        if ($this->library->usage($path) !== []) {
            return;
        }

        Storage::disk('public')->delete($path);
        Media::query()->where('path', $path)->delete();
    }
}
