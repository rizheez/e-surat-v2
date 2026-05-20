<?php

namespace Tests\Feature;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\Disposition;
use App\Models\LetterNature;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class IncomingLetterAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_signed_file_route_requires_a_valid_signature(): void
    {
        Storage::fake('local');

        $viewer = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $letter = $this->makeIncomingLetter($viewer, filePath: 'surat-masuk/test-file.pdf');

        Storage::disk('local')->put($letter->file_path, 'dummy pdf content');

        $this->actingAs($viewer)
            ->get(route('incoming-letters.file', $letter))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute('incoming-letters.file', now()->addMinutes(30), $letter);

        $this->actingAs($viewer)
            ->get($signedUrl)
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="test-file.pdf"');
    }

    public function test_confidential_letters_require_the_confidential_permission_for_show_and_file_access(): void
    {
        Storage::fake('local');

        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $viewer = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan');
        $privilegedViewer = $this->makeUser('admin-persuratan', 'FT', 'Tenaga Kependidikan Rahasia');

        $confidentialNature = LetterNature::query()->create([
            'kode' => 'RST-TEST',
            'nama' => 'Rahasia Pengujian',
            'deskripsi' => 'Khusus pengujian policy surat rahasia.',
            'level_kerahasiaan' => 1,
        ]);

        $letter = $this->makeIncomingLetter($creator, $confidentialNature, 'surat-masuk/confidential.pdf');
        Storage::disk('local')->put($letter->file_path, 'top secret');

        $this->actingAs($viewer)
            ->get(route('incoming-letters.show', $letter))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute('incoming-letters.file', now()->addMinutes(30), $letter);

        $this->actingAs($viewer)
            ->get($signedUrl)
            ->assertForbidden();

        $this->actingAs($privilegedViewer)
            ->get(route('incoming-letters.show', $letter))
            ->assertOk();

        $this->actingAs($privilegedViewer)
            ->get($signedUrl)
            ->assertOk();
    }

    public function test_regular_user_only_sees_incoming_letters_that_are_related_to_them(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $recipient = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan Terkait');
        $otherUser = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan Lain');
        $letter = $this->makeIncomingLetter($creator);

        $disposition = Disposition::create([
            'incoming_letter_id' => $letter->id,
            'sender_id' => $creator->id,
            'instruksi' => 'Tindak lanjuti surat ini.',
            'status' => 'menunggu',
        ]);

        $disposition->recipients()->create([
            'recipient_id' => $recipient->id,
            'unit_id' => $recipient->unit_id,
            'status' => 'menunggu',
        ]);

        $this->actingAs($recipient)
            ->get(route('incoming-letters.index'))
            ->assertOk()
            ->assertSee($letter->perihal);

        $this->actingAs($recipient)
            ->get(route('incoming-letters.show', $letter))
            ->assertOk();

        $this->actingAs($otherUser)
            ->get(route('incoming-letters.index'))
            ->assertOk()
            ->assertDontSee($letter->perihal);

        $this->actingAs($otherUser)
            ->get(route('incoming-letters.show', $letter))
            ->assertForbidden();
    }

    public function test_user_with_view_all_incoming_letters_can_access_unrelated_public_letter(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $globalViewer = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');
        $letter = $this->makeIncomingLetter($creator);

        $this->actingAs($globalViewer)
            ->get(route('incoming-letters.index'))
            ->assertOk()
            ->assertSee($letter->perihal);

        $this->actingAs($globalViewer)
            ->get(route('incoming-letters.show', $letter))
            ->assertOk();
    }

    private function makeIncomingLetter(
        User $creator,
        ?LetterNature $nature = null,
        ?string $filePath = null,
    ): IncomingLetter {
        $nature ??= LetterNature::query()->where('level_kerahasiaan', 0)->firstOrFail();

        return IncomingLetter::create([
            'nomor_agenda' => (string) fake()->unique()->numerify('2026/###'),
            'nomor_surat' => fake()->numerify('SM-###'),
            'tanggal_surat' => now()->subDay()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'asal_surat' => 'Kementerian Contoh',
            'perihal' => 'Surat uji akses',
            'ringkasan' => 'Ringkasan pengujian akses surat masuk.',
            'sifat_surat_id' => $nature->id,
            'file_path' => $filePath,
            'status' => IncomingLetterStatus::Baru->value,
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
