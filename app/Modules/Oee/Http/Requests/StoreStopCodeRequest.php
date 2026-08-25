<?php

namespace App\Modules\Oee\Http\Requests;

use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStopCodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('cod_stops', 'codigo')->ignore($this->catalogEntry()),
            ],
            'detalle' => ['required', 'string', 'max:255'],
            'tipo_parada' => ['required', Rule::enum(StopType::class)],
            'categoria' => ['nullable', 'string', 'max:255'],
            'causa' => ['nullable', 'string', 'max:255'],
            'recurso_afectado' => ['nullable', 'string', 'max:255'],
            'familia_oee' => ['nullable', 'string', 'max:64'],
            'es_tetra_pak' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'detalle' => 'detalle',
            'tipo_parada' => 'árbol de pérdidas',
            'es_tetra_pak' => 'Tetra Pak',
            'activo' => 'activo',
        ];
    }

    /**
     * The catalog row being edited, if any.
     */
    protected function catalogEntry(): ?CodStop
    {
        $codStop = $this->route('codStop');

        return $codStop instanceof CodStop ? $codStop : null;
    }

    /**
     * Normalise the payload before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $type = StopType::tryFrom((string) $this->input('tipo_parada'));

        $this->merge([
            'codigo' => mb_strtoupper(trim((string) $this->input('codigo'))),
            'es_tetra_pak' => $this->boolean('es_tetra_pak'),
            'activo' => $this->boolean('activo', true),
            'familia_oee' => $this->filled('familia_oee')
                ? $this->input('familia_oee')
                : $type?->value,
            'categoria' => $this->filled('categoria')
                ? $this->input('categoria')
                : $type?->label(),
        ]);
    }
}
