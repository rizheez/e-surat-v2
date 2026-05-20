<?php

namespace Tests\Feature;

use App\Enums\DispositionStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\LetterNature;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DispositionMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_monitoring_page_defaults_to_active_dispositions_and_exposes_summary_cards(): void
    {
        $sender = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $activeRecipient = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');
        $finishedRecipient = $this->makeUser('tendik', 'BAU', 'Tenaga Kependidikan');

        $active = $this->makeDisposition(
            $sender,
            [$activeRecipient],
            DispositionStatus::Diproses,
            now()->subDay()->toDateString(),
            'Monitoring Aktif',
        );

        $this->makeDisposition(
            $sender,
            [$finishedRecipient],
            DispositionStatus::Selesai,
            now()->subDays(2)->toDateString(),
            'Monitoring Selesai',
        );

        $this->actingAs($sender)
            ->get(route('dispositions.monitor'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dispositions/Monitor')
                ->where('summary.total', 1)
                ->where('summary.overdue', 1)
                ->has('dispositions.data', 1)
                ->where('dispositions.data.0.id', $active->id)
                ->where('dispositions.data.0.overdue_nodes_count', 1));
    }

    public function test_non_global_user_only_sees_dispositions_they_sent_or_received_in_monitoring(): void
    {
        $viewer = $this->makeUser('tendik', 'FT', 'Operator Fakultas');
        $viewer->givePermissionTo('create disposition');
        $otherSender = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $otherRecipient = $this->makeUser('tendik', 'BAU', 'Staf BAU');

        $visible = $this->makeDisposition(
            $otherSender,
            [$viewer],
            DispositionStatus::Menunggu,
            now()->addDay()->toDateString(),
            'Monitoring Terlihat',
        );

        $this->makeDisposition(
            $otherSender,
            [$otherRecipient],
            DispositionStatus::Menunggu,
            now()->addDays(2)->toDateString(),
            'Monitoring Tersembunyi',
        );

        $this->actingAs($viewer)
            ->get(route('dispositions.monitor'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dispositions/Monitor')
                ->has('dispositions.data', 1)
                ->where('dispositions.data.0.id', $visible->id));
    }

    private function makeDisposition(
        User $sender,
        array $recipients,
        DispositionStatus $status,
        string $deadline,
        string $subject,
    ): Disposition {
        $letter = $this->makeIncomingLetter($sender, $subject);

        $disposition = Disposition::create([
            'incoming_letter_id' => $letter->id,
            'sender_id' => $sender->id,
            'instruksi' => 'Instruksi monitoring',
            'catatan' => null,
            'batas_waktu' => $deadline,
            'status' => $status->value,
        ]);

        foreach ($recipients as $recipient) {
            $disposition->recipients()->create([
                'recipient_id' => $recipient->id,
                'unit_id' => $recipient->unit_id,
                'status' => $status->value,
                'tanggal_dibaca' => now(),
                'tanggal_selesai' => $status === DispositionStatus::Selesai ? now() : null,
            ]);
        }

        return $disposition->fresh(['incomingLetter', 'sender', 'recipients.recipient.unit', 'childrenRecursive']);
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
            'ringkasan' => 'Ringkasan surat monitoring.',
            'sifat_surat_id' => $nature->id,
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
