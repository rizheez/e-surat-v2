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
    public function incomingLetters(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('create incoming letters'), 403);

        $natures = LetterNature::query()->orderBy('kode')->get(['kode', 'nama']);

        return Excel::download(new WorkbookTemplateExport([
            new ArraySheetExport('Data', [
                'nomor_surat',
                'tanggal_surat',
                'tanggal_diterima',
                'asal_surat',
                'perihal',
                'ringkasan',
                'kode_sifat',
                'status',
            ], [[
                '093/UND/EXT/V/2026',
                '2026-05-20',
                '2026-05-21',
                'Universitas Mitra Nasional',
                'Undangan rapat koordinasi akademik',
                'Koordinasi agenda akademik semester genap.',
                $natures->first()?->kode ?? 'B',
                'baru',
            ]]),
            new ArraySheetExport('Petunjuk', ['Kolom', 'Aturan'], [
                ['nomor_surat', 'Wajib. Nomor surat asal.'],
                ['tanggal_surat', 'Wajib. Format tanggal YYYY-MM-DD.'],
                ['tanggal_diterima', 'Wajib. Format tanggal YYYY-MM-DD.'],
                ['asal_surat', 'Wajib. Nama instansi/pengirim.'],
                ['perihal', 'Wajib. Judul/perihal surat masuk.'],
                ['ringkasan', 'Opsional. Ringkasan isi surat.'],
                ['kode_sifat', 'Wajib. Gunakan kode dari sheet Referensi Sifat.'],
                ['status', 'Wajib. Nilai yang valid: baru, didisposisi, selesai, diarsipkan.'],
            ]),
            new ArraySheetExport('Referensi Sifat', ['kode_sifat', 'nama'], $natures->map(fn ($nature) => [
                $nature->kode,
                $nature->nama,
            ])->all()),
        ]), 'template-import-surat-masuk.xlsx');
    }

    public function outgoingLetters(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('manage outgoing letters'), 403);

        $categories = LetterCategory::query()->orderBy('kode')->get(['kode', 'nama']);
        $signatories = User::query()->with('position')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'position_id']);

        return Excel::download(new WorkbookTemplateExport([
            new ArraySheetExport('Data', [
                'tanggal_surat',
                'kode_kategori',
                'tujuan_surat',
                'perihal',
                'ringkasan',
                'content_mode',
                'status',
                'email_penandatangan',
                'nomor_surat_keluar',
            ], [[
                '2026-05-21',
                $categories->first()?->kode ?? 'ND',
                'Fakultas Teknik',
                'Pemberitahuan kegiatan akademik',
                'Ringkasan surat keluar untuk kebutuhan import historis.',
                'generate',
                'draft',
                $signatories->first()?->email ?? 'admin@esurat.test',
                '',
            ]]),
            new ArraySheetExport('Petunjuk', ['Kolom', 'Aturan'], [
                ['tanggal_surat', 'Wajib. Format YYYY-MM-DD.'],
                ['kode_kategori', 'Wajib. Gunakan kode dari sheet Referensi Kategori.'],
                ['tujuan_surat', 'Wajib. Tujuan/penerima surat keluar.'],
                ['perihal', 'Wajib. Perihal surat.'],
                ['ringkasan', 'Wajib. Ringkasan isi surat.'],
                ['content_mode', 'Wajib. Nilai valid: generate atau upload.'],
                ['status', 'Wajib. Nilai valid: draft, menunggu_persetujuan, perlu_revisi, disetujui, dikirim, diarsipkan.'],
                ['email_penandatangan', 'Wajib. Gunakan email dari sheet Referensi Penandatangan.'],
                ['nomor_surat_keluar', 'Opsional. Kosongkan jika ingin generate otomatis saat import.'],
            ]),
            new ArraySheetExport('Referensi Kategori', ['kode_kategori', 'nama'], $categories->map(fn ($category) => [
                $category->kode,
                $category->nama,
            ])->all()),
            new ArraySheetExport('Referensi Penandatangan', ['email', 'nama', 'jabatan'], $signatories->map(fn ($user) => [
                $user->email,
                $user->name,
                $user->position?->nama,
            ])->all()),
        ]), 'template-import-surat-keluar.xlsx');
    }

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
