<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportLetterNumberReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage outgoing letters') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ];
    }
}
