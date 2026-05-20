<?php

namespace App\Imports;

use App\Models\LetterCategory;
use App\Models\LetterNumberReservation;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class LetterNumberReservationsImport implements ToCollection, WithHeadingRow
{
    private int $imported = 0;

    public function __construct(
        private readonly User $user,
    ) {
    }

    public function collection($rows): void
    {
        foreach ($rows as $index => $row) {
            $data = array_map(
                static fn ($value) => is_string($value) ? trim($value) : $value,
                Arr::only($row->toArray(), [
                    'nomor_surat',
                    'tanggal_surat',
                    'kode_kategori',
                    'jenis_dokumen',
                    'perihal',
                    'tujuan_surat',
                    'status',
                    'catatan',
                ])
            );

            if (collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $validator = Validator::make($data, [
                'nomor_surat' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique(LetterNumberReservation::class, 'nomor_surat'),
                    Rule::unique(OutgoingLetter::class, 'nomor_surat_keluar'),
                ],
                'tanggal_surat' => ['required', 'date'],
                'kode_kategori' => ['required', Rule::exists(LetterCategory::class, 'kode')],
                'jenis_dokumen' => ['nullable', 'string', 'max:80'],
                'perihal' => ['required', 'string', 'max:255'],
                'tujuan_surat' => ['nullable', 'string', 'max:200'],
                'status' => ['required', Rule::in(['reserved', 'used', 'void', 'used_manual'])],
                'catatan' => ['nullable', 'string'],
            ], [], [
                'kode_kategori' => 'kode kategori',
                'tujuan_surat' => 'tujuan surat',
            ]);

            if ($validator->fails()) {
                throw new ExcelValidationException(
                    ValidationException::withMessages($validator->errors()->toArray()),
                    [new Failure($index + 2, 'row', $validator->errors()->all(), $data)]
                );
            }

            $category = LetterCategory::query()->where('kode', $data['kode_kategori'])->firstOrFail();
            $status = $data['status'];

            LetterNumberReservation::create([
                'nomor_surat' => $data['nomor_surat'],
                'tanggal_surat' => Carbon::parse($data['tanggal_surat'])->toDateString(),
                'kategori_surat_id' => $category->id,
                'jenis_dokumen' => $data['jenis_dokumen'] ?: null,
                'perihal' => $data['perihal'],
                'tujuan_surat' => $data['tujuan_surat'] ?: null,
                'catatan' => $data['catatan'] ?: null,
                'status' => $status,
                'created_by' => $this->user->id,
                'used_at' => in_array($status, ['used', 'used_manual'], true) ? now() : null,
            ]);

            $this->imported++;
        }
    }

    public function importedCount(): int
    {
        return $this->imported;
    }
}
