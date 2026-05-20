<?php

namespace Tests\Feature;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\OutgoingLetterApprovalRequested;
use App\Notifications\OutgoingLetterApproved;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OutgoingLetterApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Notification::fake();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_generated_outgoing_letter_can_move_from_draft_to_approved_with_notifications(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $signatory = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        $this->actingAs($creator)->post(route('outgoing-letters.store'), [
            'tanggal_surat' => '2026-05-20',
            'tujuan_surat' => 'UPT Teknologi Informasi',
            'perihal' => 'Nota dinas penguatan layanan',
            'ringkasan' => 'Ringkasan surat keluar untuk pengujian.',
            'kategori_surat_id' => $category->id,
            'signatory_user_id' => $signatory->id,
            'content_mode' => 'generate',
            'lampiran_text' => '1 berkas',
            'kepada_text' => 'Kepala UPT Teknologi Informasi',
            'lokasi_tujuan' => 'di Tempat',
            'salam_pembuka' => "Assalamu'alaikum Wr. Wb.",
            'isi_surat' => 'Mohon koordinasi pelaksanaan layanan.',
            'lampiran_detail' => 'Dokumen pendukung',
            'penutup_text' => 'Demikian disampaikan.',
            'tembusan_text' => "1. Arsip\n2. Wakil Rektor II",
        ])->assertRedirect(route('outgoing-letters.index'));

        $letter = OutgoingLetter::query()->firstOrFail();

        $this->assertSame(OutgoingLetterStatus::Draft, $letter->status);
        $this->assertSame($signatory->id, $letter->signatory_user_id);
        $this->assertStringStartsWith($category->kode.'/', $letter->nomor_surat_keluar);
        $this->assertStringContainsString('/05/2026', $letter->nomor_surat_keluar);

        $this->actingAs($creator)->patch(route('outgoing-letters.submit-approval', $letter))
            ->assertSessionHasNoErrors();

        $letter->refresh();

        $this->assertSame(OutgoingLetterStatus::MenungguPersetujuan, $letter->status);
        $this->assertNotNull($letter->approval_requested_at);
        Notification::assertSentTo($signatory, OutgoingLetterApprovalRequested::class);

        $this->actingAs($signatory)->patch(route('outgoing-letters.approve', $letter))
            ->assertSessionHasNoErrors();

        $letter->refresh();

        $this->assertSame(OutgoingLetterStatus::Disetujui, $letter->status);
        $this->assertNotNull($letter->approved_at);
        $this->assertNotNull($letter->verification_token);
        $this->assertNotNull($letter->verification_token_generated_at);
        Notification::assertSentTo($creator, OutgoingLetterApproved::class);

        $this->actingAs($creator)
            ->get(route('outgoing-letters.pdf', $letter))
            ->assertOk();
    }

    public function test_only_the_assigned_signatory_can_approve_outgoing_letter(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $signatory = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $otherLeader = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        $letter = OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/999/UNU-KT/5/2026',
            'tanggal_surat' => now()->toDateString(),
            'tujuan_surat' => 'Fakultas Teknik',
            'perihal' => 'Surat uji approval',
            'ringkasan' => 'Ringkasan approval',
            'kategori_surat_id' => $category->id,
            'signatory_user_id' => $signatory->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Dekan Fakultas Teknik',
            'lokasi_tujuan' => 'di Tempat',
            'isi_surat' => 'Isi surat pengujian.',
            'status' => OutgoingLetterStatus::MenungguPersetujuan->value,
            'approval_requested_at' => now(),
            'created_by' => $creator->id,
            'penandatangan_nama' => $signatory->name,
            'penandatangan_jabatan' => $signatory->position?->nama,
        ]);

        $this->actingAs($otherLeader)
            ->patch(route('outgoing-letters.approve', $letter))
            ->assertForbidden();

        $this->assertSame(OutgoingLetterStatus::MenungguPersetujuan, $letter->fresh()->status);
    }

    private function makeUser(string $role, string $unitCode, string $positionName): User
    {
        $unit = Unit::query()->where('kode', $unitCode)->firstOrFail();
        $position = Position::query()->firstOrCreate(
            ['nama' => $positionName, 'unit_id' => $unit->id],
            ['level' => 5],
        );

        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user->fresh(['unit', 'position']);
    }
}
