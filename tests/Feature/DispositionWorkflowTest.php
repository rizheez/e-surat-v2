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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DispositionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Notification::fake();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_recipient_can_forward_disposition_and_parent_stays_in_progress_until_child_finishes(): void
    {
        $rektor = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $dekan = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');
        $dosen = $this->makeUser('dosen', 'FT', 'Dosen');
        $letter = $this->makeIncomingLetter($rektor);

        $this->actingAs($rektor)->post(route('dispositions.store'), [
            'incoming_letter_id' => $letter->id,
            'recipient_ids' => [$dekan->id],
            'instruksi' => 'Tindak lanjuti di tingkat fakultas.',
            'catatan' => 'Prioritas tinggi.',
            'batas_waktu' => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $root = Disposition::query()->whereNull('parent_disposition_id')->firstOrFail();

        $this->actingAs($dekan)->post(route('dispositions.forward', $root), [
            'recipient_ids' => [$dosen->id],
            'instruksi' => 'Koordinasikan dengan dosen penerima.',
            'catatan' => 'Mohon selesai sebelum rapat.',
            'batas_waktu' => now()->addDays(3)->toDateString(),
        ])->assertRedirect();

        $root->refresh()->load(['recipients', 'children.recipients']);
        $child = $root->children->first();

        $this->assertNotNull($child);
        $this->assertSame($dekan->id, $child->sender_id);
        $this->assertSame(DispositionStatus::Diproses, $root->status);
        $this->assertSame(
            DispositionStatus::Diproses,
            $root->recipients->firstWhere('recipient_id', $dekan->id)?->status,
        );
        $this->assertSame(DispositionStatus::Menunggu, $child->status);
        $this->assertSame(IncomingLetterStatus::Diproses, $letter->fresh()->status);

        $this->actingAs($dosen)->patch(route('dispositions.status', $child), [
            'status' => DispositionStatus::Selesai->value,
        ])->assertRedirect();

        $root->refresh()->load('recipients');
        $child->refresh();
        $letter->refresh();

        $this->assertSame(DispositionStatus::Selesai, $child->status);
        $this->assertSame(DispositionStatus::Selesai, $root->status);
        $this->assertSame(
            DispositionStatus::Selesai,
            $root->recipients->firstWhere('recipient_id', $dekan->id)?->status,
        );
        $this->assertSame(IncomingLetterStatus::Selesai, $letter->status);
    }

    public function test_recipient_cannot_update_status_or_forward_again_after_forwarding_once(): void
    {
        $rektor = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $dekan = $this->makeUser('pimpinan-fakultas', 'FT', 'Dekan Fakultas Teknik');
        $dosen = $this->makeUser('dosen', 'FT', 'Dosen');
        $letter = $this->makeIncomingLetter($rektor);

        $this->actingAs($rektor)->post(route('dispositions.store'), [
            'incoming_letter_id' => $letter->id,
            'recipient_ids' => [$dekan->id],
            'instruksi' => 'Tindak lanjuti di tingkat fakultas.',
            'catatan' => null,
            'batas_waktu' => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $root = Disposition::query()->whereNull('parent_disposition_id')->firstOrFail();

        $this->actingAs($dekan)->post(route('dispositions.forward', $root), [
            'recipient_ids' => [$dosen->id],
            'instruksi' => 'Koordinasikan dengan dosen penerima.',
            'catatan' => null,
            'batas_waktu' => now()->addDays(3)->toDateString(),
        ])->assertRedirect();

        $this->actingAs($dekan)->patch(route('dispositions.status', $root), [
            'status' => DispositionStatus::Selesai->value,
        ])->assertForbidden();

        $this->actingAs($dekan)->post(route('dispositions.forward', $root), [
            'recipient_ids' => [$dosen->id],
            'instruksi' => 'Forward ulang yang tidak sah.',
            'catatan' => null,
            'batas_waktu' => now()->addDays(2)->toDateString(),
        ])->assertForbidden();

        $this->actingAs($dekan)->post(route('dispositions.followups.store', $root), [
            'catatan' => 'Tindak lanjut setelah forward.',
            'status' => DispositionStatus::Diproses->value,
        ])->assertForbidden();

        $this->assertCount(1, $root->fresh()->children);
    }

    public function test_forward_scope_allows_cross_unit_service_targets_but_rejects_unrelated_units(): void
    {
        $operatorFt = $this->makeUser('tendik', 'FT', 'Operator Fakultas');
        $operatorFt->givePermissionTo('create disposition');
        $staffUpt = $this->makeUser('tendik', 'UPT-TI', 'Tenaga Kependidikan');
        $staffBau = $this->makeUser('tendik', 'BAU', 'Tenaga Kependidikan');
        $letter = $this->makeIncomingLetter($operatorFt);

        $this->actingAs($operatorFt)->post(route('dispositions.store'), [
            'incoming_letter_id' => $letter->id,
            'recipient_ids' => [$operatorFt->id],
            'instruksi' => 'Proses di tingkat fakultas.',
            'catatan' => null,
            'batas_waktu' => now()->addDays(7)->toDateString(),
        ])->assertRedirect();

        $root = Disposition::query()->whereNull('parent_disposition_id')->firstOrFail();

        $this->actingAs($operatorFt)->post(route('dispositions.forward', $root), [
            'recipient_ids' => [$staffUpt->id],
            'instruksi' => 'Mohon dukungan teknis.',
            'catatan' => null,
            'batas_waktu' => now()->addDays(2)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('dispositions', [
            'parent_disposition_id' => $root->id,
            'sender_id' => $operatorFt->id,
        ]);

        $this->actingAs($operatorFt)->post(route('dispositions.forward', $root), [
            'recipient_ids' => [$staffBau->id],
            'instruksi' => 'Coba lintas unit yang tidak sah.',
            'catatan' => null,
            'batas_waktu' => now()->addDays(2)->toDateString(),
        ])->assertForbidden();
    }

    public function test_initial_disposition_create_page_must_be_opened_from_incoming_letter_context(): void
    {
        $rektor = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');
        $letter = $this->makeIncomingLetter($rektor);

        $this->actingAs($rektor)
            ->get(route('dispositions.create'))
            ->assertRedirect(route('incoming-letters.index'));

        $this->actingAs($rektor)
            ->get(route('dispositions.create', ['incoming_letter_id' => $letter->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dispositions/Create')
                ->where('selectedIncomingLetterId', $letter->id)
                ->where('letter.id', $letter->id)
                ->where('letter.perihal', $letter->perihal));
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
            'perihal' => 'Undangan koordinasi akademik',
            'ringkasan' => 'Ringkasan surat masuk untuk pengujian.',
            'sifat_surat_id' => $nature->id,
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
