<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForwardDispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['required', 'exists:users,id'],
            'instruksi' => ['required', 'string'],
            'catatan' => ['nullable', 'string'],
            'batas_waktu' => ['nullable', 'date'],
        ];
    }
}
