<?php

namespace App\Modules\Oee\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSkusRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:64'],
            'linea' => ['nullable', 'string', Rule::in($this->lines())],
            'activo' => ['nullable', 'in:0,1'],
        ];
    }

    /**
     * @return array{search: string, linea: string, activo: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search') ?? '',
            'linea' => $this->validated('linea') ?? '',
            'activo' => $this->validated('activo') ?? '',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->input('search') ?: null,
            'linea' => $this->input('linea') ?: null,
            'activo' => $this->filled('activo') ? $this->input('activo') : null,
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
