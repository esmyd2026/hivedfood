<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(self::configurationRules(), [
            'plan_id' => ['nullable', 'integer', 'exists:pricing_plans,id'],
        ]);
    }

    /**
     * Reglas compartidas con el guardado de la cotización.
     *
     * @return array<string, mixed>
     */
    public static function configurationRules(): array
    {
        return [
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'business_type_note' => ['nullable', 'string', 'max:160'],

            // Modalidad de cobro y volumen declarado por sucursal. El volumen
            // es informativo salvo que la modalidad cobre consumo.
            'billing_model_id' => ['nullable', 'integer', 'exists:billing_models,id'],
            'orders_per_branch' => ['nullable', 'integer', 'min:0', 'max:1000000'],

            'items' => ['nullable', 'array', 'max:60'],
            'items.*.id' => ['required', 'integer', 'exists:pricing_items,id'],

            // Escalabilidad: cantidades totales, no incrementos.
            'scalability.branches' => ['nullable', 'integer', 'min:1', 'max:200'],
            'scalability.brands' => ['nullable', 'integer', 'min:1', 'max:50'],
            'scalability.whatsapp_numbers' => ['nullable', 'integer', 'min:1', 'max:50'],
            'scalability.trainings' => ['nullable', 'integer', 'min:0', 'max:20'],

            // Dimensionamiento del catálogo: informativo, nunca cobrado.
            'catalog.products' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'catalog.categories' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'catalog.variations' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'catalog.extras' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'catalog.option_groups' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'catalog.distribution' => ['nullable', 'string', 'max:1000'],

            'notes' => ['nullable', 'string', 'max:1000'],
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
