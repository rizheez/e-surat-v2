<?php

use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            ['kode' => '01.1', 'nama' => 'Urusan Umum', 'deskripsi' => 'Internal'],
            ['kode' => '01.2', 'nama' => 'Urusan Administrasi', 'deskripsi' => 'Internal'],
            ['kode' => '01.3', 'nama' => 'Urusan Akademik', 'deskripsi' => 'Internal'],
            ['kode' => '01.4', 'nama' => 'Urusan Kepegawaian', 'deskripsi' => 'Internal'],
            ['kode' => '01.5', 'nama' => 'Urusan Keuangan', 'deskripsi' => 'Internal'],
            ['kode' => '01.6', 'nama' => 'Urusan Kehumasan', 'deskripsi' => 'Internal'],
            ['kode' => '01.7', 'nama' => 'Urusan Penelitian', 'deskripsi' => 'Internal'],
            ['kode' => '01.8', 'nama' => 'Urusan Keagamaan', 'deskripsi' => 'Internal'],
            ['kode' => '01.9', 'nama' => 'Urusan Sosial', 'deskripsi' => 'Internal'],
            ['kode' => '01.10', 'nama' => 'Urusan Kemahasiswaan', 'deskripsi' => 'Internal'],
            ['kode' => '02.1', 'nama' => 'Urusan Umum', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.2', 'nama' => 'Urusan Administrasi', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.3', 'nama' => 'Urusan Akademik', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.4', 'nama' => 'Urusan Kepegawaian', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.5', 'nama' => 'Urusan Keuangan', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.6', 'nama' => 'Urusan Kehumasan', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.7', 'nama' => 'Urusan Penelitian', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.8', 'nama' => 'Urusan Keagamaan', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.9', 'nama' => 'Urusan Sosial', 'deskripsi' => 'Eksternal'],
            ['kode' => '02.10', 'nama' => 'Urusan Kemahasiswaan', 'deskripsi' => 'Eksternal'],
            ['kode' => 'KB', 'nama' => 'Kesepakatan Bersama', 'deskripsi' => 'Khusus'],
            ['kode' => 'ND', 'nama' => 'Nota Dinas', 'deskripsi' => 'Khusus'],
            ['kode' => 'PK', 'nama' => 'Perjanjian Kerjasama', 'deskripsi' => 'Khusus'],
            ['kode' => 'S.Kep', 'nama' => 'Surat Keputusan', 'deskripsi' => 'Khusus'],
            ['kode' => 'SPTDD', 'nama' => 'Surat Perintah Perjalanan Dinas (Dalam Daerah)', 'deskripsi' => 'Khusus'],
            ['kode' => 'SPTLD', 'nama' => 'Surat Perintah Perjalanan Dinas (Luar Daerah)', 'deskripsi' => 'Khusus'],
            ['kode' => 'SPTLN', 'nama' => 'Surat Perintah Perjalanan Dinas (Luar Negeri)', 'deskripsi' => 'Khusus'],
        ];

        foreach ($categories as $category) {
            LetterCategory::updateOrCreate(
                ['kode' => $category['kode']],
                ['nama' => $category['nama'], 'deskripsi' => $category['deskripsi']],
            );
        }

        $officialCodes = collect($categories)->pluck('kode')->all();

        LetterCategory::query()
            ->whereNotIn('kode', $officialCodes)
            ->get()
            ->filter(function (LetterCategory $category) {
                $isUsedByIncoming = IncomingLetter::where('kategori_surat_id', $category->id)->exists();
                $isUsedByOutgoing = OutgoingLetter::where('kategori_surat_id', $category->id)->exists();

                return !$isUsedByIncoming && !$isUsedByOutgoing;
            })
            ->each
            ->delete();
    }

    public function down(): void
    {
        // no-op
    }
};
