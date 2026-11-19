<?php

namespace App\Http\Controllers\Admin;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TestimonialController extends AdminResourceController
{
    protected array $searchable = ['author', 'business', 'quote'];

    protected array $uploads = ['avatar'];

    protected string $mediaFolder = 'testimonios';

    protected function model(): string
    {
        return Testimonial::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'author' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'business' => ['nullable', 'string', 'max:120'],
            'avatar' => ['nullable', 'file', 'image', 'max:2048'],
            'quote' => ['required', 'string', 'max:1000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
