<?php

namespace Tests\Feature;

use App\Models\IncomingLetter;
use App\Models\LetterNature;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\IncomingLetterCreated;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_notification_read_marks_the_notification_as_read_and_redirects_to_its_target(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $recipient = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $letter = $this->makeIncomingLetter($creator);

        $recipient->notifications()->delete();
        $recipient->notify(new IncomingLetterCreated($letter));
        $notification = $recipient->fresh()->notifications()->firstOrFail();

        $this->actingAs($recipient)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('incoming-letters.show', $letter));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_read_all_marks_every_unread_notification_as_read(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $recipient = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $firstLetter = $this->makeIncomingLetter($creator);
        $secondLetter = $this->makeIncomingLetter($creator);

        $recipient->notifications()->delete();
        $recipient->notify(new IncomingLetterCreated($firstLetter));
        $recipient->notify(new IncomingLetterCreated($secondLetter));

        $this->assertCount(2, $recipient->fresh()->unreadNotifications);

        $this->actingAs($recipient)
            ->post(route('notifications.read-all'))
            ->assertSessionHas('success');

        $this->assertCount(0, $recipient->fresh()->unreadNotifications);
        $this->assertCount(2, $recipient->fresh()->notifications()->whereNotNull('read_at')->get());
    }

    public function test_users_cannot_mark_other_users_notifications_as_read(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $owner = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $intruder = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');
        $letter = $this->makeIncomingLetter($creator);

        $owner->notifications()->delete();
        $owner->notify(new IncomingLetterCreated($letter));
        $notification = $owner->fresh()->notifications()->firstOrFail();

        $this->actingAs($intruder)
            ->post(route('notifications.read', $notification))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    private function makeIncomingLetter(User $creator): IncomingLetter
    {
        $nature = LetterNature::query()->firstOrFail();

        return IncomingLetter::create([
            'nomor_agenda' => (string) fake()->unique()->numerify('2026/###'),
            'nomor_surat' => fake()->numerify('SM-###'),
            'tanggal_surat' => now()->subDay()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'asal_surat' => 'Kementerian Contoh',
            'perihal' => 'Surat notifikasi pengujian',
            'ringkasan' => 'Ringkasan surat untuk pengujian notifikasi.',
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
