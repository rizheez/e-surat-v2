<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incoming_letter_id' => ['required', 'exists:incoming_letters,id'],
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['required', 'exists:users,id'],
            'instruksi' => ['required', 'string'],
            'catatan' => ['nullable', 'string'],
            'batas_waktu' => ['nullable', 'date'],
        ];
    }
}
