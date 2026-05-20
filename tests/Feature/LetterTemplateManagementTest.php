<?php

namespace Tests\Feature;

use App\Models\LetterCategory;
use App\Models\LetterTemplate;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_manager_can_create_update_and_delete_letter_templates(): void
    {
        $manager = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $category = LetterCategory::query()->where('kode', 'UND')->firstOrFail();

        $this->actingAs($manager)->post(route('master-data.letter-templates.store'), [
            'nama' => 'Template Undangan Rapat',
            'kategori_surat_id' => $category->id,
            'tujuan_surat' => 'Seluruh Ketua Program Studi',
            'perihal' => 'Undangan rapat koordinasi',
            'ringkasan' => 'Ringkasan template undangan.',
            'lampiran_text' => '1 berkas',
            'kepada_text' => 'Ketua Program Studi',
            'lokasi_tujuan' => 'di Tempat',
            'salam_pembuka' => "Assalamu'alaikum Wr. Wb.",
            'isi_surat' => 'Kami mengundang Bapak/Ibu untuk hadir pada rapat koordinasi.',
            'lampiran_detail' => 'Agenda rapat',
            'penutup_text' => 'Demikian undangan ini disampaikan.',
            'tembusan_text' => '1. Arsip',
        ])->assertSessionHas('success');

        $template = LetterTemplate::query()->where('nama', 'Template Undangan Rapat')->firstOrFail();

        $this->actingAs($manager)->patch(route('master-data.letter-templates.update', $template), [
            'nama' => 'Template Undangan Rapat Revisi',
            'kategori_surat_id' => $category->id,
            'tujuan_surat' => 'Seluruh Ketua Program Studi',
            'perihal' => 'Undangan rapat koordinasi revisi',
            'ringkasan' => 'Ringkasan template undangan revisi.',
            'lampiran_text' => '2 berkas',
            'kepada_text' => 'Ketua Program Studi',
            'lokasi_tujuan' => 'Banjarmasin',
            'salam_pembuka' => "Assalamu'alaikum Wr. Wb.",
            'isi_surat' => 'Isi template revisi untuk rapat koordinasi.',
            'lampiran_detail' => 'Agenda rapat\nDaftar hadir',
            'penutup_text' => 'Demikian revisi undangan ini disampaikan.',
            'tembusan_text' => '1. Arsip\n2. Wakil Rektor',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('letter_templates', [
            'id' => $template->id,
            'nama' => 'Template Undangan Rapat Revisi',
            'perihal' => 'Undangan rapat koordinasi revisi',
            'lampiran_text' => '2 berkas',
        ]);

        $this->actingAs($manager)
            ->delete(route('master-data.letter-templates.destroy', $template))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('letter_templates', ['id' => $template->id]);
    }

    public function test_non_manager_cannot_manage_letter_templates(): void
    {
        $user = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan');
        $category = LetterCategory::query()->where('kode', 'UND')->firstOrFail();

        $this->actingAs($user)->post(route('master-data.letter-templates.store'), [
            'nama' => 'Template Tidak Boleh',
            'kategori_surat_id' => $category->id,
            'perihal' => 'Percobaan',
            'isi_surat' => 'Percobaan isi surat.',
        ])->assertForbidden();
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
