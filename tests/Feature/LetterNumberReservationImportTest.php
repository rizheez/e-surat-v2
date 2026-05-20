<?php

namespace Tests\Feature;

use App\Models\LetterCategory;
use App\Models\LetterNumberReservation;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LetterNumberReservationImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_manager_can_import_letter_number_reservations_with_used_manual_status(): void
    {
        $manager = $this->makeUser('admin-persuratan');
        $category = LetterCategory::query()->where('kode', 'SK')->firstOrFail();
        $path = $this->makeWorkbook([
            ['nomor_surat', 'tanggal_surat', 'kode_kategori', 'jenis_dokumen', 'perihal', 'tujuan_surat', 'status', 'catatan'],
            ['SK/77/UNU-KT/05/2026', '2026-05-21', $category->kode, 'Transkrip', 'Import nomor manual', 'Mahasiswa TI', 'used_manual', 'Dipakai di luar upload surat keluar'],
            ['SK/78/UNU-KT/05/2026', '2026-05-21', $category->kode, 'Surat Tugas', 'Import nomor reservasi', 'Dosen FT', 'reserved', 'Siap dipakai nanti'],
        ]);

        $this->actingAs($manager)
            ->post(route('letter-number-reservations.import'), [
                'file' => new \Illuminate\Http\UploadedFile($path, 'reservations.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('letter_number_reservations', [
            'nomor_surat' => 'SK/77/UNU-KT/05/2026',
            'status' => 'used_manual',
            'created_by' => $manager->id,
        ]);

        $this->assertDatabaseHas('letter_number_reservations', [
            'nomor_surat' => 'SK/78/UNU-KT/05/2026',
            'status' => 'reserved',
            'created_by' => $manager->id,
        ]);

        $this->assertNotNull(LetterNumberReservation::query()->where('nomor_surat', 'SK/77/UNU-KT/05/2026')->firstOrFail()->used_at);
    }

    public function test_import_rejects_duplicate_number_used_by_outgoing_letter(): void
    {
        $manager = $this->makeUser('admin-persuratan');
        $category = LetterCategory::query()->where('kode', 'SK')->firstOrFail();

        \App\Models\OutgoingLetter::create([
            'nomor_surat_keluar' => 'SK/88/UNU-KT/05/2026',
            'tanggal_surat' => '2026-05-21',
            'tujuan_surat' => 'Fakultas Teknik',
            'perihal' => 'Sudah terpakai',
            'ringkasan' => 'Nomor sudah dipakai surat keluar.',
            'kategori_surat_id' => $category->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Dekan FT',
            'isi_surat' => 'Isi surat.',
            'status' => 'draft',
            'created_by' => $manager->id,
        ]);

        $path = $this->makeWorkbook([
            ['nomor_surat', 'tanggal_surat', 'kode_kategori', 'jenis_dokumen', 'perihal', 'tujuan_surat', 'status', 'catatan'],
            ['SK/88/UNU-KT/05/2026', '2026-05-21', $category->kode, 'Transkrip', 'Bentrok nomor', 'Mahasiswa TI', 'used_manual', 'Harus gagal'],
        ]);

        $this->actingAs($manager)
            ->post(route('letter-number-reservations.import'), [
                'file' => new \Illuminate\Http\UploadedFile($path, 'duplicate.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('letter_number_reservations', [
            'nomor_surat' => 'SK/88/UNU-KT/05/2026',
            'perihal' => 'Bentrok nomor',
        ]);
    }

    private function makeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'reservation-import-');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function makeUser(string $role): User
    {
        $unit = Unit::query()->where('kode', 'BAU')->firstOrFail();
        $position = Position::query()->where('nama', 'Kepala Biro Administrasi Umum')->firstOrFail();
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
