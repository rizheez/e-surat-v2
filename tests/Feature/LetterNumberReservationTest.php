<?php

namespace Tests\Feature;

use App\Models\LetterCategory;
use App\Models\LetterNumberReservation;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterNumberReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_manager_can_reserve_number_and_use_it_for_uploaded_outgoing_letter(): void
    {
        Storage::fake('local');
        $manager = $this->makeUser('admin-persuratan');
        $category = LetterCategory::query()->where('kode', 'SK')->firstOrFail();

        $this->actingAs($manager)->post(route('letter-number-reservations.store'), [
            'tanggal_surat' => '2026-05-21',
            'kategori_surat_id' => $category->id,
            'jenis_dokumen' => 'Surat Tugas',
            'perihal' => 'Penugasan narasumber seminar',
            'tujuan_surat' => 'Dosen Fakultas Teknik',
            'catatan' => 'Dibuat di Word lalu diunggah sebagai PDF.',
        ])->assertSessionHas('success');

        $reservation = LetterNumberReservation::query()->firstOrFail();

        $this->assertSame('reserved', $reservation->status);
        $this->assertSame('SK/1/UNU-KT/05/2026', $reservation->nomor_surat);

        $this->actingAs($manager)->post(route('outgoing-letters.store'), [
            'letter_number_reservation_id' => $reservation->id,
            'tanggal_surat' => '2026-05-21',
            'tujuan_surat' => 'Dosen Fakultas Teknik',
            'perihal' => 'Penugasan narasumber seminar',
            'ringkasan' => 'Surat tugas dari dokumen Word.',
            'kategori_surat_id' => $category->id,
            'content_mode' => 'upload',
            'file_surat' => UploadedFile::fake()->create('surat-tugas.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('outgoing-letters.index'));

        $reservation->refresh();

        $this->assertSame('used', $reservation->status);
        $this->assertNotNull($reservation->used_by_outgoing_letter_id);
        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $reservation->used_by_outgoing_letter_id,
            'nomor_surat_keluar' => 'SK/1/UNU-KT/05/2026',
            'content_mode' => 'upload',
        ]);
    }

    public function test_reserved_number_blocks_next_generated_sequence(): void
    {
        $manager = $this->makeUser('admin-persuratan');
        $category = LetterCategory::query()->where('kode', 'UND')->firstOrFail();

        $this->actingAs($manager)->post(route('letter-number-reservations.store'), [
            'tanggal_surat' => '2026-05-21',
            'kategori_surat_id' => $category->id,
            'perihal' => 'Undangan rapat koordinasi',
        ]);

        $this->actingAs($manager)->post(route('letter-number-reservations.store'), [
            'tanggal_surat' => '2026-05-21',
            'kategori_surat_id' => $category->id,
            'perihal' => 'Undangan rapat evaluasi',
        ]);

        $this->assertDatabaseHas('letter_number_reservations', ['nomor_surat' => 'UND/1/UNU-KT/05/2026']);
        $this->assertDatabaseHas('letter_number_reservations', ['nomor_surat' => 'UND/2/UNU-KT/05/2026']);
    }

    public function test_reserved_number_can_be_voided_before_use(): void
    {
        $manager = $this->makeUser('admin-persuratan');
        $category = LetterCategory::query()->where('kode', 'ND')->firstOrFail();
        $reservation = LetterNumberReservation::create([
            'nomor_surat' => 'ND/1/UNU-KT/05/2026',
            'tanggal_surat' => '2026-05-21',
            'kategori_surat_id' => $category->id,
            'perihal' => 'Nota dinas batal',
            'status' => 'reserved',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->patch(route('letter-number-reservations.void', $reservation))
            ->assertSessionHas('success');

        $this->assertSame('void', $reservation->fresh()->status);
    }

    private function makeUser(string $role): User
    {
        $unit = Unit::query()->where('kode', 'BAU')->firstOrFail();
        $position = Position::query()->where('nama', 'Kepala Biro Administrasi Umum')->firstOrFail();
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
