<?php

namespace Tests\Feature;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\LetterNature;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncomingLetterCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_authorized_user_can_create_incoming_letter_with_pdf_upload(): void
    {
        Storage::fake('local');

        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $nature = LetterNature::query()->firstOrFail();
        $this->actingAs($creator)->post(route('incoming-letters.store'), [
            'nomor_surat' => 'SM-001/TEST',
            'tanggal_surat' => now()->subDay()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'asal_surat' => 'Kementerian Contoh',
            'perihal' => 'Surat Masuk Baru',
            'ringkasan' => 'Ringkasan surat masuk baru.',
            'sifat_surat_id' => $nature->id,
            'status' => IncomingLetterStatus::Baru->value,
            'file_surat' => UploadedFile::fake()->create('surat-masuk.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('incoming-letters.index'));

        $letter = IncomingLetter::query()
            ->where('nomor_surat', 'SM-001/TEST')
            ->firstOrFail();

        $this->assertNotNull($letter->nomor_agenda);
        $this->assertMatchesRegularExpression('/^SM\/\d{3}\/\d{4}$/', $letter->nomor_agenda);
        $this->assertNotNull($letter->file_path);
        Storage::disk('local')->assertExists($letter->file_path);
    }

    public function test_updating_incoming_letter_replaces_the_file_and_removes_the_old_one(): void
    {
        Storage::fake('local');

        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $letter = $this->makeIncomingLetter($creator, 'surat-masuk/old-file.pdf');
        Storage::disk('local')->put($letter->file_path, 'old file');

        $this->travel(1)->seconds();

        $this->actingAs($creator)->patch(route('incoming-letters.update', $letter), [
            'nomor_surat' => $letter->nomor_surat,
            'tanggal_surat' => $letter->tanggal_surat->toDateString(),
            'tanggal_diterima' => $letter->tanggal_diterima->toDateString(),
            'asal_surat' => $letter->asal_surat,
            'perihal' => 'Surat Masuk Diperbarui',
            'ringkasan' => $letter->ringkasan,
            'sifat_surat_id' => $letter->sifat_surat_id,
            'status' => IncomingLetterStatus::Baru->value,
            'file_surat' => UploadedFile::fake()->create('surat-baru.pdf', 200, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $letter->refresh();

        $this->assertSame('Surat Masuk Diperbarui', $letter->perihal);
        $this->assertNotSame('surat-masuk/old-file.pdf', $letter->file_path);
        Storage::disk('local')->assertMissing('surat-masuk/old-file.pdf');
        Storage::disk('local')->assertExists($letter->file_path);
    }

    public function test_deleting_incoming_letter_removes_the_uploaded_file(): void
    {
        Storage::fake('local');

        $creator = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $letter = $this->makeIncomingLetter($creator, 'surat-masuk/delete-me.pdf');
        Storage::disk('local')->put($letter->file_path, 'delete me');

        $this->actingAs($creator)
            ->delete(route('incoming-letters.destroy', $letter))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('incoming_letters', ['id' => $letter->id]);
        Storage::disk('local')->assertMissing('surat-masuk/delete-me.pdf');
    }

    private function makeIncomingLetter(User $creator, string $filePath): IncomingLetter
    {
        $nature = LetterNature::query()->firstOrFail();

        return IncomingLetter::create([
            'nomor_agenda' => (string) fake()->unique()->numerify('2026/###'),
            'nomor_surat' => fake()->numerify('SM-###'),
            'tanggal_surat' => now()->subDay()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'asal_surat' => 'Kementerian Contoh',
            'perihal' => 'Surat uji CRUD',
            'ringkasan' => 'Ringkasan surat masuk untuk pengujian CRUD.',
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
