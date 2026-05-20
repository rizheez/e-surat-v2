<?php

namespace App\Http\Requests;

use App\Models\LetterCategory;
use App\Models\LetterTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage master data') ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:120'],
            'kategori_surat_id' => ['required', Rule::exists(LetterCategory::class, 'id')],
            'tujuan_surat' => ['nullable', 'string', 'max:200'],
            'perihal' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'lampiran_text' => ['nullable', 'string', 'max:150'],
            'kepada_text' => ['nullable', 'string', 'max:200'],
            'lokasi_tujuan' => ['nullable', 'string', 'max:150'],
            'salam_pembuka' => ['nullable', 'string'],
            'isi_surat' => ['required', 'string'],
            'lampiran_detail' => ['nullable', 'string'],
            'penutup_text' => ['nullable', 'string'],
            'tembusan_text' => ['nullable', 'string'],
        ];
    }
}
