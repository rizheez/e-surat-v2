<?php

namespace App\Http\Requests;

use App\Models\LetterCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterNumberReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage outgoing letters') ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal_surat' => ['required', 'date'],
            'kategori_surat_id' => ['required', Rule::exists(LetterCategory::class, 'id')],
            'jenis_dokumen' => ['nullable', 'string', 'max:80'],
            'perihal' => ['required', 'string', 'max:255'],
            'tujuan_surat' => ['nullable', 'string', 'max:200'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
