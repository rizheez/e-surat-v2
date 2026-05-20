<?php

namespace App\Http\Requests;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterCategory;
use App\Models\LetterNumberReservation;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class OutgoingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var OutgoingLetter|null $outgoingLetter */
        $outgoingLetter = $this->route('outgoingLetter');

        return $outgoingLetter
            ? $this->user()?->can('update', $outgoingLetter) ?? false
            : $this->user()?->can('create', OutgoingLetter::class) ?? false;
    }

    public function rules(): array
    {
        $letterId = $this->route('outgoingLetter')?->id;

        return [
            'tanggal_surat' => ['required', 'date'],
            'tujuan_surat' => ['required', 'string', 'max:200'],
            'perihal' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'kategori_surat_id' => ['required', Rule::exists(LetterCategory::class, 'id')],
            'letter_number_reservation_id' => ['nullable', Rule::exists(LetterNumberReservation::class, 'id')->where(fn ($query) => $query->where('status', 'reserved'))],
            'signatory_user_id' => ['nullable', Rule::exists(User::class, 'id')->where(fn ($query) => $query->where('is_active', true))],
            'content_mode' => ['required', Rule::in(['upload', 'generate'])],
            'lampiran_text' => ['nullable', 'string', 'max:150'],
            'kepada_text' => ['nullable', 'string', 'max:200'],
            'lokasi_tujuan' => ['nullable', 'string', 'max:150'],
            'salam_pembuka' => ['nullable', 'string'],
            'isi_surat' => ['nullable', 'string'],
            'lampiran_detail' => ['nullable', 'string'],
            'penutup_text' => ['nullable', 'string'],
            'tembusan_text' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(OutgoingLetterStatus::class)],
            'file_surat' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $mode = $this->input('content_mode');
                $hasExistingFile = (bool) $this->route('outgoingLetter')?->file_path;

                if ($mode === 'upload' && !$this->hasFile('file_surat') && !$hasExistingFile) {
                    $validator->errors()->add('file_surat', 'File PDF wajib diunggah untuk mode upload.');
                }

                if ($mode === 'generate') {
                    foreach ([
                        'kepada_text' => 'Tujuan surat wajib diisi untuk mode generate.',
                        'isi_surat' => 'Isi surat wajib diisi untuk mode generate.',
                        'signatory_user_id' => 'Penandatangan wajib dipilih untuk mode generate.',
                    ] as $field => $message) {
                        if (!filled($this->input($field))) {
                            $validator->errors()->add($field, $message);
                        }
                    }
                }
            },
        ];
    }
}
