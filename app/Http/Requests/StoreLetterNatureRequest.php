<?php

namespace App\Http\Requests;

use App\Models\LetterNature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterNatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage master data') ?? false;
    }

    public function rules(): array
    {
        /** @var LetterNature|null $letterNature */
        $letterNature = $this->route('letterNature');

        return [
            'nama' => ['required', 'string', 'max:50'],
            'kode' => ['required', 'string', 'max:20', Rule::unique(LetterNature::class)->ignore($letterNature?->id)],
            'level_kerahasiaan' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }
}
