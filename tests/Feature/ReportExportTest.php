<?php

namespace Tests\Feature;

use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_export_incoming_letters_with_active_filters(): void
    {
        $admin = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $nature = LetterNature::query()->where('kode', 'B')->firstOrFail();
        $secretNature = LetterNature::query()->where('kode', 'R')->firstOrFail();

        IncomingLetter::create([
            'nomor_agenda' => '2026/900',
            'nomor_surat' => '900/EXT/V/2026',
            'tanggal_surat' => '2026-05-19',
            'tanggal_diterima' => '2026-05-20',
            'asal_surat' => 'Kementerian Pendidikan',
            'perihal' => 'Surat export visible',
            'ringkasan' => 'Ringkasan export visible',
            'sifat_surat_id' => $nature->id,
            'status' => IncomingLetterStatus::Baru->value,
            'created_by' => $admin->id,
        ]);

        IncomingLetter::create([
            'nomor_agenda' => '2026/901',
            'nomor_surat' => '901/EXT/V/2026',
            'tanggal_surat' => '2026-05-19',
            'tanggal_diterima' => '2026-05-20',
            'asal_surat' => 'Kementerian Rahasia',
            'perihal' => 'Surat rahasia export',
            'ringkasan' => 'Ringkasan rahasia',
            'sifat_surat_id' => $secretNature->id,
            'status' => IncomingLetterStatus::Baru->value,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.incoming-letters.csv', [
            'search' => 'export',
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Surat export visible', $content);
        $this->assertStringContainsString('Surat rahasia export', $content);
    }

    public function test_user_without_export_permission_cannot_export_reports(): void
    {
        $user = User::query()->where('email', 'dosen@esurat.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.incoming-letters.csv'))
            ->assertForbidden();
    }

    public function test_incoming_export_respects_confidential_permission(): void
    {
        $user = $this->makeUser('dosen');
        $user->givePermissionTo('export reports');
        $publicNature = LetterNature::query()->where('kode', 'B')->firstOrFail();
        $secretNature = LetterNature::query()->where('kode', 'R')->firstOrFail();

        IncomingLetter::create([
            'nomor_agenda' => '2026/902',
            'nomor_surat' => '902/EXT/V/2026',
            'tanggal_surat' => '2026-05-19',
            'tanggal_diterima' => '2026-05-20',
            'asal_surat' => 'Publik',
            'perihal' => 'Surat publik export',
            'ringkasan' => 'Ringkasan publik',
            'sifat_surat_id' => $publicNature->id,
            'status' => IncomingLetterStatus::Baru->value,
            'created_by' => $user->id,
        ]);

        IncomingLetter::create([
            'nomor_agenda' => '2026/903',
            'nomor_surat' => '903/EXT/V/2026',
            'tanggal_surat' => '2026-05-19',
            'tanggal_diterima' => '2026-05-20',
            'asal_surat' => 'Rahasia',
            'perihal' => 'Surat rahasia tersembunyi',
            'ringkasan' => 'Ringkasan rahasia',
            'sifat_surat_id' => $secretNature->id,
            'status' => IncomingLetterStatus::Baru->value,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.incoming-letters.csv'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Surat publik export', $content);
        $this->assertStringNotContainsString('Surat rahasia tersembunyi', $content);
    }

    public function test_admin_can_export_outgoing_letters_with_active_filters(): void
    {
        $admin = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/910/UNU-KT/05/2026',
            'tanggal_surat' => '2026-05-20',
            'tujuan_surat' => 'UPT Teknologi Informasi',
            'perihal' => 'Surat keluar export',
            'ringkasan' => 'Ringkasan surat keluar export',
            'kategori_surat_id' => $category->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Kepala UPT TI',
            'isi_surat' => 'Isi surat keluar export.',
            'status' => OutgoingLetterStatus::Draft->value,
            'created_by' => $admin->id,
        ]);

        OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/911/UNU-KT/05/2026',
            'tanggal_surat' => '2026-05-20',
            'tujuan_surat' => 'Fakultas Teknik',
            'perihal' => 'Surat keluar lain',
            'ringkasan' => 'Ringkasan lain',
            'kategori_surat_id' => $category->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Dekan FT',
            'isi_surat' => 'Isi surat lain.',
            'status' => OutgoingLetterStatus::Draft->value,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.outgoing-letters.csv', [
            'search' => 'export',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Surat keluar export', $content);
        $this->assertStringNotContainsString('Surat keluar lain', $content);
    }

    public function test_admin_can_export_dispositions_with_active_filters(): void
    {
        $admin = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $recipient = User::query()->where('email', 'dosen@esurat.test')->firstOrFail();
        $letter = IncomingLetter::query()->firstOrFail();

        $disposition = Disposition::create([
            'incoming_letter_id' => $letter->id,
            'sender_id' => $admin->id,
            'instruksi' => 'Instruksi export disposisi',
            'catatan' => 'Catatan export disposisi',
            'batas_waktu' => '2026-05-25',
            'status' => DispositionStatus::Menunggu->value,
        ]);
        $disposition->recipients()->create([
            'recipient_id' => $recipient->id,
            'unit_id' => $recipient->unit_id,
            'status' => DispositionStatus::Menunggu->value,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.dispositions.csv', [
            'search' => $letter->nomor_agenda,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Instruksi export disposisi', $content);
        $this->assertStringContainsString($recipient->name, $content);
    }

    public function test_admin_can_export_archives(): void
    {
        $admin = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $nature = LetterNature::query()->where('kode', 'B')->firstOrFail();
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        IncomingLetter::create([
            'nomor_agenda' => '2026/904',
            'nomor_surat' => '904/EXT/V/2026',
            'tanggal_surat' => '2026-05-19',
            'tanggal_diterima' => '2026-05-20',
            'asal_surat' => 'Arsip Masuk',
            'perihal' => 'Arsip masuk export',
            'ringkasan' => 'Ringkasan arsip masuk',
            'sifat_surat_id' => $nature->id,
            'status' => IncomingLetterStatus::Diarsipkan->value,
            'created_by' => $admin->id,
        ]);

        OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/912/UNU-KT/05/2026',
            'tanggal_surat' => '2026-05-20',
            'tujuan_surat' => 'Arsip Keluar',
            'perihal' => 'Arsip keluar export',
            'ringkasan' => 'Ringkasan arsip keluar',
            'kategori_surat_id' => $category->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Tujuan arsip',
            'isi_surat' => 'Isi arsip keluar.',
            'status' => OutgoingLetterStatus::Diarsipkan->value,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.archives.csv', [
            'year' => '2026',
            'month' => '5',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Arsip masuk export', $content);
        $this->assertStringContainsString('Arsip keluar export', $content);
    }

    private function makeUser(string $role): User
    {
        $unit = Unit::query()->where('kode', 'FT')->firstOrFail();
        $position = Position::query()->where('nama', 'Dosen')->firstOrFail();
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
