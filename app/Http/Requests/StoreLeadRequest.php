<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'business_type_label' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', Rule::in(Lead::SOURCES)],
            'accepts_marketing' => ['nullable', 'boolean'],
            'quotation_token' => ['nullable', 'uuid'],

            // Anti-spam: hidden field that must stay empty, plus a minimum
            // time between rendering and submitting the form.
            'website' => ['prohibited'],
            'elapsed' => ['nullable', 'integer', 'min:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Necesitamos tu nombre para poder contactarte.',
            'email.required' => 'Indícanos un correo de contacto.',
            'email.email' => 'El correo no parece válido.',
            'website.prohibited' => 'No pudimos procesar el formulario.',
            'elapsed.min' => 'El formulario se envió demasiado rápido. Inténtalo de nuevo.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'phone' => 'teléfono',
            'company' => 'empresa',
            'message' => 'mensaje',
        ];
    }
}
