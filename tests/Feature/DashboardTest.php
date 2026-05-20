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
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_dashboard_shows_monitoring_summaries_for_dispositions_and_approvals(): void
    {
        $viewer = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $sender = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $recipient = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');
        $signatory = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor Penandatangan');

        $letter = $this->makeIncomingLetter($sender, 'Dashboard Monitoring');

        $disposition = Disposition::create([
            'incoming_letter_id' => $letter->id,
            'sender_id' => $sender->id,
            'instruksi' => 'Instruksi dashboard',
            'batas_waktu' => now()->subDay()->toDateString(),
            'status' => DispositionStatus::Diproses->value,
        ]);
        $disposition->recipients()->create([
            'recipient_id' => $recipient->id,
            'unit_id' => $recipient->unit_id,
            'status' => DispositionStatus::Diproses->value,
        ]);

        $child = Disposition::create([
            'incoming_letter_id' => $letter->id,
            'parent_disposition_id' => $disposition->id,
            'sender_id' => $recipient->id,
            'instruksi' => 'Instruksi turunan',
            'batas_waktu' => now()->addDay()->toDateString(),
            'status' => DispositionStatus::Menunggu->value,
        ]);
        $child->recipients()->create([
            'recipient_id' => $sender->id,
            'unit_id' => $sender->unit_id,
            'status' => DispositionStatus::Menunggu->value,
        ]);

        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();
        OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/500/UNU-KT/5/2026',
            'tanggal_surat' => now()->toDateString(),
            'tujuan_surat' => 'UPT Teknologi Informasi',
            'perihal' => 'Approval Tertahan',
            'ringkasan' => 'Ringkasan approval tertahan.',
            'kategori_surat_id' => $category->id,
            'signatory_user_id' => $signatory->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Kepala UPT Teknologi Informasi',
            'lokasi_tujuan' => 'di Tempat',
            'isi_surat' => 'Isi approval.',
            'status' => OutgoingLetterStatus::MenungguPersetujuan->value,
            'approval_requested_at' => now()->subDays(3),
            'created_by' => $sender->id,
            'penandatangan_nama' => $signatory->name,
            'penandatangan_jabatan' => $signatory->position?->nama,
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('monitoring.dispositions.overdue', 1)
                ->where('monitoring.dispositions.forwarded', 1)
                ->where('monitoring.approvals.pending', 1)
                ->where('monitoring.approvals.stuck', 1)
                ->has('latestApprovals', 1)
                ->has('alerts.stuckApprovals', 1));
    }

    private function makeIncomingLetter(User $creator, string $subject): IncomingLetter
    {
        $nature = LetterNature::query()->firstOrFail();

        return IncomingLetter::create([
            'nomor_agenda' => (string) fake()->unique()->numerify('2026/###'),
            'nomor_surat' => fake()->numerify('SM-###'),
            'tanggal_surat' => now()->subDay()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'asal_surat' => 'Kementerian Contoh',
            'perihal' => $subject,
            'ringkasan' => 'Ringkasan dashboard.',
            'sifat_surat_id' => $nature->id,
            'status' => IncomingLetterStatus::Didisposisi->value,
            'created_by' => $creator->id,
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
