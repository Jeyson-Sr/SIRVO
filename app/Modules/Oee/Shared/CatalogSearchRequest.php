<?php

namespace App\Modules\Oee\Shared;

use App\Modules\Oee\Stops\StopType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogSearchRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:64'],
            'linea' => ['nullable', 'string', 'max:32'],
            'tipo' => ['nullable', Rule::enum(StopType::class)],
        ];
    }
}
