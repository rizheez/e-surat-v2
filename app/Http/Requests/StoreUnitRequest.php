<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage master data') ?? false;
    }

    public function rules(): array
    {
        /** @var Unit|null $unit */
        $unit = $this->route('unit');

        return [
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', Rule::unique(Unit::class)->ignore($unit?->id)],
            'parent_id' => ['nullable', 'exists:units,id'],
            'is_cross_unit_target' => ['nullable', 'boolean'],
        ];
    }
}
