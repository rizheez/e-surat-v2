<?php

namespace Tests\Feature;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\OutgoingLetterApprovalReminder;
use App\Services\OutgoingLetterReminderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_stale_pending_approvals_send_a_reminder_to_the_signatory_once_per_day(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $signatory = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $letter = $this->makeLetter(
            $creator,
            $signatory,
            OutgoingLetterStatus::MenungguPersetujuan,
            now()->subDays(3),
        );

        $service = app(OutgoingLetterReminderService::class);
        $sentFirst = $service->sendStaleReminders(now()->startOfDay());
        $sentSecond = $service->sendStaleReminders(now()->startOfDay());

        $this->assertSame(1, $sentFirst);
        $this->assertSame(0, $sentSecond);
        $this->assertSame(
            1,
            $signatory->notifications()
                ->where('type', OutgoingLetterApprovalReminder::class)
                ->where('data->letter_id', $letter->id)
                ->where('data->reminder_type', OutgoingLetterReminderService::PENDING_APPROVAL)
                ->count(),
        );
    }

    public function test_stale_revision_requests_send_a_reminder_to_the_creator(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $signatory = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $letter = $this->makeLetter(
            $creator,
            $signatory,
            OutgoingLetterStatus::PerluRevisi,
            null,
            now()->subDays(3),
            'Mohon lengkapi lampiran.',
        );

        $sent = app(OutgoingLetterReminderService::class)->sendStaleReminders(now()->startOfDay());

        $this->assertSame(1, $sent);
        $this->assertSame(
            1,
            $creator->notifications()
                ->where('type', OutgoingLetterApprovalReminder::class)
                ->where('data->letter_id', $letter->id)
                ->where('data->reminder_type', OutgoingLetterReminderService::PENDING_REVISION)
                ->count(),
        );
    }

    private function makeLetter(
        User $creator,
        User $signatory,
        OutgoingLetterStatus $status,
        ?\Illuminate\Support\Carbon $approvalRequestedAt = null,
        ?\Illuminate\Support\Carbon $updatedAt = null,
        ?string $approvalNote = null,
    ): OutgoingLetter {
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        $letter = OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/'.fake()->unique()->numerify('###').'/UNU-KT/5/2026',
            'tanggal_surat' => now()->toDateString(),
            'tujuan_surat' => 'UPT Teknologi Informasi',
            'perihal' => 'Reminder approval surat keluar',
            'ringkasan' => 'Ringkasan reminder approval.',
            'kategori_surat_id' => $category->id,
            'signatory_user_id' => $signatory->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Kepala UPT Teknologi Informasi',
            'lokasi_tujuan' => 'di Tempat',
            'isi_surat' => 'Isi reminder approval.',
            'status' => $status->value,
            'approval_requested_at' => $approvalRequestedAt,
            'approval_note' => $approvalNote,
            'created_by' => $creator->id,
            'penandatangan_nama' => $signatory->name,
            'penandatangan_jabatan' => $signatory->position?->nama,
            'created_at' => now()->subDays(5),
            'updated_at' => $updatedAt ?? now()->subDays(5),
        ]);

        if ($updatedAt) {
            $letter->forceFill(['updated_at' => $updatedAt])->saveQuietly();
        }

        return $letter->fresh(['createdBy', 'signatory']);
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
