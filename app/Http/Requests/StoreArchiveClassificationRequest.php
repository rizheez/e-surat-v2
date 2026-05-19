<?php

namespace App\Http\Requests;

use App\Models\ArchiveClassification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArchiveClassificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage master data') ?? false;
    }

    public function rules(): array
    {
        /** @var ArchiveClassification|null $archiveClassification */
        $archiveClassification = $this->route('archiveClassification');

        return [
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', Rule::unique(ArchiveClassification::class)->ignore($archiveClassification?->id)],
            'masa_retensi' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
