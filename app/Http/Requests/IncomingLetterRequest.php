<?php

namespace App\Http\Requests;

use App\Enums\IncomingLetterStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IncomingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fileRule = $this->isMethod('post') ? 'nullable' : 'nullable';

        return [
            'nomor_surat' => ['required', 'string', 'max:100'],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_diterima' => ['required', 'date'],
            'asal_surat' => ['required', 'string', 'max:200'],
            'perihal' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'sifat_surat_id' => ['required', 'exists:letter_natures,id'],
            'kategori_surat_id' => ['required', 'exists:letter_categories,id'],
            'status' => ['nullable', new Enum(IncomingLetterStatus::class)],
            'file_surat' => [$fileRule, 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
