<?php

namespace App\Http\Requests;

use App\Models\LetterCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage master data') ?? false;
    }

    public function rules(): array
    {
        /** @var LetterCategory|null $letterCategory */
        $letterCategory = $this->route('letterCategory');

        return [
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', Rule::unique(LetterCategory::class)->ignore($letterCategory?->id)],
            'deskripsi' => ['nullable', 'string'],
        ];
    }
}
