<?php

namespace Tests\Feature;

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

class ArchiveAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_archive_hides_confidential_incoming_letters_from_users_without_permission(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $viewer = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan');
        $viewer->givePermissionTo('view archives');

        $publicNature = LetterNature::query()->where('level_kerahasiaan', 0)->firstOrFail();
        $confidentialNature = LetterNature::query()->create([
            'kode' => 'ARS-RAH',
            'nama' => 'Arsip Rahasia',
            'deskripsi' => 'Untuk pengujian arsip rahasia.',
            'level_kerahasiaan' => 1,
        ]);

        $publicLetter = $this->makeIncomingLetter($creator, $publicNature, 'Arsip Terbuka');
        $confidentialLetter = $this->makeIncomingLetter($creator, $confidentialNature, 'Arsip Rahasia');
        $this->attachRecipientToLetter($publicLetter, $creator, $viewer);
        $this->attachRecipientToLetter($confidentialLetter, $creator, $viewer);

        $this->actingAs($viewer)
            ->get(route('archives.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Archives/Index')
                ->has('incomingLetters.data', 1)
                ->where('incomingLetters.data.0.perihal', 'Arsip Terbuka'));
    }

    public function test_archive_shows_confidential_items_to_users_with_permission_and_includes_generated_outgoing_preview_url(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $viewer = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $viewer->givePermissionTo('view archives');
        $viewer->givePermissionTo('view outgoing letters');

        $confidentialNature = LetterNature::query()->create([
            'kode' => 'ARS-RAH-2',
            'nama' => 'Arsip Rahasia 2',
            'deskripsi' => 'Untuk pengujian akses arsip rahasia.',
            'level_kerahasiaan' => 1,
        ]);
        $category = LetterCategory::query()->firstOrFail();
        $signatory = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor Penandatangan');

        $incoming = $this->makeIncomingLetter($creator, $confidentialNature, 'Arsip Rahasia 2');

        $outgoing = OutgoingLetter::create([
            'nomor_surat_keluar' => 'ND/100/UNU-KT/5/2026',
            'tanggal_surat' => now()->toDateString(),
            'tujuan_surat' => 'UPT Teknologi Informasi',
            'perihal' => 'Arsip Surat Keluar Generated',
            'ringkasan' => 'Ringkasan arsip surat keluar generated.',
            'kategori_surat_id' => $category->id,
            'signatory_user_id' => $signatory->id,
            'content_mode' => 'generate',
            'kepada_text' => 'Kepala UPT Teknologi Informasi',
            'lokasi_tujuan' => 'di Tempat',
            'isi_surat' => 'Isi surat generated.',
            'status' => OutgoingLetterStatus::Diarsipkan->value,
            'created_by' => $creator->id,
            'penandatangan_nama' => $signatory->name,
            'penandatangan_jabatan' => $signatory->position?->nama,
        ]);

        $this->actingAs($viewer)
            ->get(route('archives.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Archives/Index')
                ->has('incomingLetters.data', 1)
                ->where('incomingLetters.data.0.id', $incoming->id)
                ->has('outgoingLetters.data', 1)
                ->where('outgoingLetters.data.0.id', $outgoing->id)
                ->where('outgoingLetters.data.0.has_file', false)
                ->where('outgoingLetters.data.0.preview_url', route('outgoing-letters.preview', $outgoing)));
    }

    public function test_archive_hides_unrelated_incoming_archives_from_regular_user(): void
    {
        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $viewer = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan');
        $viewer->givePermissionTo('view archives');

        $publicNature = LetterNature::query()->where('level_kerahasiaan', 0)->firstOrFail();
        $relatedLetter = $this->makeIncomingLetter($creator, $publicNature, 'Arsip Terkait');
        $unrelatedLetter = $this->makeIncomingLetter($creator, $publicNature, 'Arsip Tidak Terkait');

        $this->attachRecipientToLetter($relatedLetter, $creator, $viewer);

        $this->actingAs($viewer)
            ->get(route('archives.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Archives/Index')
                ->has('incomingLetters.data', 1)
                ->where('incomingLetters.data.0.perihal', 'Arsip Terkait'));
    }

    private function makeIncomingLetter(User $creator, LetterNature $nature, string $perihal): IncomingLetter
    {
        return IncomingLetter::create([
            'nomor_agenda' => (string) fake()->unique()->numerify('2026/###'),
            'nomor_surat' => fake()->numerify('SM-###'),
            'tanggal_surat' => now()->subDay()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'asal_surat' => 'Kementerian Contoh',
            'perihal' => $perihal,
            'ringkasan' => 'Ringkasan arsip untuk pengujian.',
            'sifat_surat_id' => $nature->id,
            'status' => IncomingLetterStatus::Diarsipkan->value,
            'created_by' => $creator->id,
        ]);
    }

    private function attachRecipientToLetter(IncomingLetter $letter, User $sender, User $recipient): void
    {
        $disposition = Disposition::create([
            'incoming_letter_id' => $letter->id,
            'sender_id' => $sender->id,
            'instruksi' => 'Tindak lanjuti arsip terkait.',
            'status' => 'menunggu',
        ]);

        $disposition->recipients()->create([
            'recipient_id' => $recipient->id,
            'unit_id' => $recipient->unit_id,
            'status' => 'menunggu',
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
