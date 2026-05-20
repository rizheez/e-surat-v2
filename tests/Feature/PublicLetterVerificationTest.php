<?php

namespace Tests\Feature;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLetterVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_can_verify_approved_outgoing_letter_with_valid_token(): void
    {
        $letter = $this->makeOutgoingLetter([
            'status' => OutgoingLetterStatus::Disetujui->value,
            'approved_at' => now(),
            'verification_token' => 'valid-verification-token',
            'verification_token_generated_at' => now(),
        ]);

        $this->get(route('public.outgoing-letters.verify', $letter->verification_token))
            ->assertOk()
            ->assertSee('Dokumen valid')
            ->assertSee($letter->nomor_surat_keluar)
            ->assertSee($letter->perihal)
            ->assertSee($letter->tujuan_surat);
    }

    public function test_public_verification_rejects_invalid_token(): void
    {
        $this->get(route('public.outgoing-letters.verify', 'missing-token'))
            ->assertOk()
            ->assertSee('Dokumen tidak valid')
            ->assertDontSee('Nomor surat');
    }

    public function test_public_verification_rejects_letter_that_is_not_approved(): void
    {
        $letter = $this->makeOutgoingLetter([
            'status' => OutgoingLetterStatus::Draft->value,
            'approved_at' => null,
            'verification_token' => 'draft-verification-token',
            'verification_token_generated_at' => now(),
        ]);

        $this->get(route('public.outgoing-letters.verify', $letter->verification_token))
            ->assertOk()
            ->assertSee('Dokumen tidak valid')
            ->assertDontSee($letter->nomor_surat_keluar);
    }

    private function makeOutgoingLetter(array $overrides = []): OutgoingLetter
    {
        $unit = Unit::query()->where('kode', 'RKT')->firstOrFail();
        $position = Position::query()->where('nama', 'Rektor')->firstOrFail();
        $signatory = User::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
        $creator = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        return OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/998/UNU-KT/05/2026',
            'tanggal_surat' => '2026-05-20',
            'tujuan_surat' => 'UPT Teknologi Informasi',
            'perihal' => 'Nota dinas verifikasi QR',
            'ringkasan' => 'Ringkasan surat untuk verifikasi QR.',
            'kategori_surat_id' => $category->id,
            'signatory_user_id' => $signatory->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Kepala UPT Teknologi Informasi',
            'lokasi_tujuan' => 'di Tempat',
            'isi_surat' => 'Isi surat pengujian verifikasi.',
            'status' => OutgoingLetterStatus::Disetujui->value,
            'approval_requested_at' => now()->subDay(),
            'approved_at' => now(),
            'created_by' => $creator->id,
            'penandatangan_nama' => $signatory->name,
            'penandatangan_jabatan' => $position->nama,
            ...$overrides,
        ]);
    }
}
