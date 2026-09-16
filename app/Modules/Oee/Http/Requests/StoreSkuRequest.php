<?php

namespace App\Modules\Oee\Http\Requests;

use App\Modules\Oee\Models\OeeSku;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkuRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:32',
                Rule::unique('oee_skus', 'sku')
                    ->where(fn ($query) => $query->where('linea', $this->input('linea')))
                    ->ignore($this->catalogEntry()),
            ],
            'linea' => ['required', 'string', Rule::in($this->lines())],
            'descripcion' => ['required', 'string', 'max:255'],
            'formato' => ['nullable', 'string', 'max:32'],
            'marca' => ['nullable', 'string', 'max:64'],
            'sabor' => ['nullable', 'string', 'max:64'],
            'um' => ['required', 'integer', 'min:1', 'max:1000'],
            'bph' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'compania' => ['nullable', 'string', 'max:64'],
            'mercado' => ['nullable', 'string', 'max:64'],
            'nivel' => ['required', 'integer', 'min:1', 'max:100'],
            'paq_cama' => ['required', 'integer', 'min:1', 'max:1000'],
            'cartones' => ['required', 'integer', 'min:0', 'max:1000'],
            'activo' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'linea' => 'línea',
            'descripcion' => 'descripción',
            'formato' => 'contenido',
            'um' => 'U.M',
            'bph' => 'BPH',
            'compania' => 'compañía',
            'mercado' => 'mercado',
            'nivel' => 'nivel',
            'paq_cama' => 'paq. cama',
            'cartones' => 'cartones',
            'activo' => 'activo',
        ];
    }

    /**
     * The catalog row being edited, if any.
     */
    protected function catalogEntry(): ?OeeSku
    {
        $sku = $this->route('oeeSku');

        return $sku instanceof OeeSku ? $sku : null;
    }

    /**
     * Normalise the payload before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => trim((string) $this->input('sku')),
            'descripcion' => trim((string) $this->input('descripcion')),
            'formato' => $this->filled('formato') ? trim((string) $this->input('formato')) : null,
            'marca' => $this->filled('marca') ? trim((string) $this->input('marca')) : null,
            'sabor' => $this->filled('sabor') ? trim((string) $this->input('sabor')) : null,
            'compania' => $this->filled('compania') ? trim((string) $this->input('compania')) : null,
            'mercado' => $this->filled('mercado') ? trim((string) $this->input('mercado')) : null,
            'activo' => $this->boolean('activo', true),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function lines(): array
    {
        /** @var array<int, string> $lines */
        $lines = require database_path('data/oee_lines.php');

        return $lines;
    }
}
