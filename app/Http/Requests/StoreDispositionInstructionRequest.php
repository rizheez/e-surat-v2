<?php

namespace App\Http\Requests;

use App\Models\DispositionInstruction;
use Illuminate\Foundation\Http\FormRequest;

class StoreDispositionInstructionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage master data') ?? false;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:120'],
            'isi_instruksi' => ['required', 'string'],
        ];
    }
}
