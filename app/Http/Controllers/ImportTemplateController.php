<?php

namespace App\Http\Controllers;

use App\Exports\Templates\ArraySheetExport;
use App\Exports\Templates\WorkbookTemplateExport;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateController extends Controller
{
    public function dispositions(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('create disposition'), 403);

        $users = User::query()->with('position')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'position_id']);

        return Excel::download(new WorkbookTemplateExport([
            new ArraySheetExport('Data', [
                'nomor_agenda',
                'nomor_surat_masuk',
                'email_pengirim',
                'email_penerima',
                'instruksi',
                'catatan',
                'batas_waktu',
                'status',
            ], [[
                '2026/001',
                '',
                $users->first()?->email ?? 'admin@esurat.test',
                $users->skip(1)->first()?->email ?? 'dosen@esurat.test',
                'Pelajari dan tindak lanjuti surat ini.',
                'Tolong siapkan jawaban sebelum rapat pimpinan.',
                '2026-05-24',
                'menunggu',
            ]]),
            new ArraySheetExport('Petunjuk', ['Kolom', 'Aturan'], [
                ['nomor_agenda', 'Isi nomor_agenda atau nomor_surat_masuk. Salah satu wajib terisi.'],
                ['nomor_surat_masuk', 'Alternatif lookup surat masuk bila nomor_agenda kosong.'],
                ['email_pengirim', 'Wajib. Gunakan email user aktif dari sheet Referensi Pengguna.'],
                ['email_penerima', 'Wajib. Satu email penerima per baris pada tahap awal import.'],
                ['instruksi', 'Wajib. Isi instruksi disposisi.'],
                ['catatan', 'Opsional. Catatan tambahan.'],
                ['batas_waktu', 'Opsional. Format YYYY-MM-DD.'],
                ['status', 'Wajib. Nilai valid: menunggu, dibaca, diproses, selesai.'],
            ]),
            new ArraySheetExport('Referensi Pengguna', ['email', 'nama', 'jabatan'], $users->map(fn ($user) => [
                $user->email,
                $user->name,
                $user->position?->nama,
            ])->all()),
        ]), 'template-import-disposisi.xlsx');
    }

    public function letterNumberReservations(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('manage outgoing letters'), 403);

        $categories = LetterCategory::query()->orderBy('kode')->get(['kode', 'nama']);

        return Excel::download(new WorkbookTemplateExport([
            new ArraySheetExport('Data', [
                'nomor_surat',
                'tanggal_surat',
                'kode_kategori',
                'jenis_dokumen',
                'perihal',
                'tujuan_surat',
                'status',
                'catatan',
            ], [[
                'TR/15/UNU-KT/05/2026',
                '2026-05-21',
                $categories->first()?->kode ?? 'SK',
                'Transkrip',
                'Penerbitan transkrip sementara',
                'Mahasiswa Program Studi Teknik Informatika',
                'used_manual',
                'Nomor dipakai di luar workflow upload surat keluar.',
            ]]),
            new ArraySheetExport('Petunjuk', ['Kolom', 'Aturan'], [
                ['nomor_surat', 'Wajib. Nomor surat/nomor dokumen yang akan diimport.'],
                ['tanggal_surat', 'Wajib. Format YYYY-MM-DD.'],
                ['kode_kategori', 'Wajib. Gunakan kode dari sheet Referensi Kategori.'],
                ['jenis_dokumen', 'Opsional. Contoh: Surat Tugas, Pengumuman, Transkrip.'],
                ['perihal', 'Wajib. Perihal dokumen.'],
                ['tujuan_surat', 'Opsional. Tujuan/penerima dokumen.'],
                ['status', 'Wajib. Nilai valid: reserved, used, void, used_manual.'],
                ['catatan', 'Opsional. Catatan tambahan.'],
            ]),
            new ArraySheetExport('Referensi Kategori', ['kode_kategori', 'nama'], $categories->map(fn ($category) => [
                $category->kode,
                $category->nama,
            ])->all()),
        ]), 'template-import-penomoran-surat.xlsx');
    }
}
