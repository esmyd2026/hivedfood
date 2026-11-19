<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(CalculateQuotationRequest::configurationRules(), [
            // Guardar una propuesta sí exige haber elegido paquete.
            'plan_id' => ['required', 'integer', 'exists:pricing_plans,id'],

            'contact.name' => ['required', 'string', 'min:2', 'max:120'],
            'contact.company' => ['nullable', 'string', 'max:160'],
            'contact.email' => ['required', 'email:rfc', 'max:190'],
            'contact.phone' => ['nullable', 'string', 'max:40'],
            'contact.city' => ['nullable', 'string', 'max:120'],
            'contact.accepts_marketing' => ['nullable', 'boolean'],

            'send_email' => ['nullable', 'boolean'],

            'website' => ['prohibited'],
            'elapsed' => ['nullable', 'integer', 'min:2000'],
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Elige el paquete que quieres cotizar.',
            'contact.name.required' => 'Necesitamos tu nombre para preparar la cotización.',
            'contact.email.required' => 'Indícanos un correo donde enviarte la propuesta.',
            'contact.email.email' => 'El correo no parece válido.',
            'website.prohibited' => 'No pudimos procesar el formulario.',
            'elapsed.min' => 'El formulario se envió demasiado rápido. Inténtalo de nuevo.',
        ];
    }

    /** @return array<string, mixed> */
    public function configuration(): array
    {
        return [
            'business_type_id' => $this->input('business_type_id'),
            'business_type_note' => $this->input('business_type_note'),
            'plan_id' => $this->input('plan_id'),
            'billing_model_id' => $this->input('billing_model_id'),
            'orders_per_branch' => $this->input('orders_per_branch'),
            'items' => $this->input('items', []),
            'scalability' => (array) $this->input('scalability', []),
            'catalog' => (array) $this->input('catalog', []),
            'notes' => $this->input('notes'),
        ];
    }
}
