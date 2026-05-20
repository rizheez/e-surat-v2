<?php

namespace Tests\Feature;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OutgoingLetterMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_monitoring_page_defaults_to_active_approval_statuses_and_exposes_summary(): void
    {
        $viewer = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $signatory = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor Penandatangan');

        $pending = $this->makeLetter(
            $creator,
            $signatory,
            OutgoingLetterStatus::MenungguPersetujuan,
            'Monitor Approval Pending',
            now()->subDays(3),
        );

        $this->makeLetter(
            $creator,
            $signatory,
            OutgoingLetterStatus::PerluRevisi,
            'Monitor Approval Revisi',
            now()->subDay(),
            'Perbaiki lampiran.',
        );

        $this->makeLetter(
            $creator,
            $signatory,
            OutgoingLetterStatus::Draft,
            'Monitor Approval Draft',
        );

        $this->actingAs($viewer)
            ->get(route('outgoing-letters.monitor'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OutgoingLetters/Monitor')
                ->where('summary.total', 2)
                ->where('summary.pending', 1)
                ->where('summary.revision', 1)
                ->where('summary.stuck', 1)
                ->has('letters.data', 2)
                ->where('letters.data.0.status', OutgoingLetterStatus::PerluRevisi->value)
                ->where('letters.data.1.id', $pending->id)
                ->where('letters.data.1.is_stuck', true));
    }

    public function test_monitoring_page_can_filter_by_signatory(): void
    {
        $viewer = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $signatoryA = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor A');
        $signatoryB = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');

        $visible = $this->makeLetter(
            $creator,
            $signatoryA,
            OutgoingLetterStatus::MenungguPersetujuan,
            'Surat ke Signatory A',
            now()->subDay(),
        );

        $this->makeLetter(
            $creator,
            $signatoryB,
            OutgoingLetterStatus::MenungguPersetujuan,
            'Surat ke Signatory B',
            now()->subDay(),
        );

        $this->actingAs($viewer)
            ->get(route('outgoing-letters.monitor', ['signatory_id' => $signatoryA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OutgoingLetters/Monitor')
                ->has('letters.data', 1)
                ->where('letters.data.0.id', $visible->id));
    }

    private function makeLetter(
        User $creator,
        User $signatory,
        OutgoingLetterStatus $status,
        string $subject,
        ?\Illuminate\Support\Carbon $approvalRequestedAt = null,
        ?string $approvalNote = null,
    ): OutgoingLetter {
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        return OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/'.fake()->unique()->numerify('###').'/UNU-KT/5/2026',
            'tanggal_surat' => now()->toDateString(),
            'tujuan_surat' => 'UPT Teknologi Informasi',
            'perihal' => $subject,
            'ringkasan' => 'Ringkasan monitoring approval.',
            'kategori_surat_id' => $category->id,
            'signatory_user_id' => $signatory->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Kepala UPT Teknologi Informasi',
            'lokasi_tujuan' => 'di Tempat',
            'isi_surat' => 'Isi surat monitoring approval.',
            'status' => $status->value,
            'approval_requested_at' => $approvalRequestedAt,
            'approved_at' => $status === OutgoingLetterStatus::Disetujui ? now() : null,
            'approval_note' => $approvalNote,
            'created_by' => $creator->id,
            'penandatangan_nama' => $signatory->name,
            'penandatangan_jabatan' => $signatory->position?->nama,
        ]);
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
