<?php

namespace App\Http\Requests;

use App\Enums\DispositionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DispositionFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan' => ['required', 'string'],
            'status' => ['required', new Enum(DispositionStatus::class)],
            'file_tindak_lanjut' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
