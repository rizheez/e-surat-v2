<?php

namespace Tests\Feature;

use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\LetterNature;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\DispositionDeadlineReminder;
use App\Services\DispositionReminderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispositionReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_due_soon_dispositions_send_h2_reminder_once_per_day(): void
    {
        $sender = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $recipient = $this->makeUser('dosen', true);
        $disposition = $this->makeDisposition($sender, $recipient, now()->addDays(2)->toDateString());

        $service = app(DispositionReminderService::class);
        $sentFirst = $service->sendDueSoonReminders(now()->startOfDay());
        $sentSecond = $service->sendDueSoonReminders(now()->startOfDay());

        $this->assertSame(1, $sentFirst);
        $this->assertSame(0, $sentSecond);
        $this->assertSame(
            1,
            $recipient->notifications()
                ->where('type', DispositionDeadlineReminder::class)
                ->where('data->disposition_id', $disposition->id)
                ->where('data->reminder_type', DispositionReminderService::H2_REMINDER)
                ->where('data->reminder_date', now()->toDateString())
                ->count(),
        );
    }

    public function test_disposition_reminder_dry_run_counts_without_sending_notifications(): void
    {
        $sender = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $recipient = $this->makeUser('dosen', true);
        $this->makeDisposition($sender, $recipient, now()->addDays(2)->toDateString());

        $sent = app(DispositionReminderService::class)->sendDueSoonReminders(now()->startOfDay(), dryRun: true);

        $this->assertSame(1, $sent);
        $this->assertSame(0, $recipient->notifications()->count());
    }

    public function test_disposition_reminder_skips_completed_and_inactive_targets(): void
    {
        $sender = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $activeRecipient = $this->makeUser('dosen', true);
        $inactiveRecipient = $this->makeUser('dosen', false);
        $completed = $this->makeDisposition($sender, $activeRecipient, now()->addDays(2)->toDateString(), DispositionStatus::Selesai);
        $inactive = $this->makeDisposition($sender, $inactiveRecipient, now()->addDays(2)->toDateString());

        $sent = app(DispositionReminderService::class)->sendDueSoonReminders(now()->startOfDay());

        $this->assertSame(0, $sent);
        $this->assertSame(0, $activeRecipient->notifications()->where('data->disposition_id', $completed->id)->count());
        $this->assertSame(0, $inactiveRecipient->notifications()->where('data->disposition_id', $inactive->id)->count());
    }

    public function test_disposition_reminder_command_supports_dry_run(): void
    {
        $sender = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $recipient = $this->makeUser('dosen', true);
        $this->makeDisposition($sender, $recipient, now()->addDays(2)->toDateString());

        $this->artisan('dispositions:send-deadline-reminders', [
            '--date' => now()->toDateString(),
            '--dry-run' => true,
        ])->expectsOutput('Calon reminder deadline disposisi: 1')->assertExitCode(0);

        $this->assertSame(0, $recipient->notifications()->count());
    }

    private function makeDisposition(
        User $sender,
        User $recipient,
        string $deadline,
        DispositionStatus $status = DispositionStatus::Menunggu,
    ): Disposition {
        $nature = LetterNature::query()->where('kode', 'B')->firstOrFail();
        $letter = IncomingLetter::create([
            'nomor_agenda' => fake()->unique()->numerify('2026/###'),
            'nomor_surat' => fake()->unique()->numerify('REM/###/V/2026'),
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'asal_surat' => 'Unit Pengujian Reminder',
            'perihal' => 'Reminder disposisi mendekati deadline',
            'ringkasan' => 'Ringkasan reminder disposisi.',
            'sifat_surat_id' => $nature->id,
            'status' => IncomingLetterStatus::Didisposisi->value,
            'created_by' => $sender->id,
        ]);

        $disposition = Disposition::create([
            'incoming_letter_id' => $letter->id,
            'sender_id' => $sender->id,
            'instruksi' => 'Mohon tindak lanjuti sebelum deadline.',
            'batas_waktu' => $deadline,
            'status' => $status->value,
        ]);

        $disposition->recipients()->create([
            'recipient_id' => $recipient->id,
            'unit_id' => $recipient->unit_id,
            'status' => $status->value,
        ]);

        return $disposition->fresh(['incomingLetter', 'recipients.recipient']);
    }

    private function makeUser(string $role, bool $active): User
    {
        $unit = Unit::query()->where('kode', 'FT')->firstOrFail();
        $position = Position::query()->where('nama', 'Dosen')->firstOrFail();
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'is_active' => $active,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
