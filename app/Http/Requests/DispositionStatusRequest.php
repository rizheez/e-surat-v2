<?php

namespace App\Http\Requests;

use App\Enums\DispositionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DispositionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(DispositionStatus::class)],
        ];
    }
}
