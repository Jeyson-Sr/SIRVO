<?php

namespace App\Modules\Oee\Http\Requests;

use App\Modules\Oee\Enums\StopType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListStopCodesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:64'],
            'tipo_parada' => ['nullable', 'string', 'max:8'],
            'es_tetra_pak' => ['nullable', 'in:0,1'],
            'activo' => ['nullable', 'in:0,1'],
        ];
    }

    /**
     * @return array{search: string, tipo_parada: string, es_tetra_pak: string, activo: string}
     */
    public function filters(): array
    {
        $tipo = StopType::tryFrom((string) ($this->validated('tipo_parada') ?? ''));

        return [
            'search' => $this->validated('search') ?? '',
            'tipo_parada' => $tipo?->value ?? '',
            'es_tetra_pak' => $this->validated('es_tetra_pak') ?? '',
            'activo' => $this->validated('activo') ?? '',
        ];
    }

    public function stopType(): ?StopType
    {
        return StopType::tryFrom((string) ($this->validated('tipo_parada') ?? ''));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->input('search') ?: null,
            'tipo_parada' => $this->input('tipo_parada') ?: null,
            'es_tetra_pak' => $this->filled('es_tetra_pak') ? $this->input('es_tetra_pak') : null,
            'activo' => $this->filled('activo') ? $this->input('activo') : null,
        ]);
    }
}
