<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_authorized_users_can_download_import_templates(): void
    {
        $incomingManager = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $outgoingManager = $this->makeUser('admin-persuratan', 'BAU', 'Kepala Biro Administrasi Umum');
        $dispositionManager = $this->makeUser('pimpinan-universitas', 'RKT', 'Rektor');

        $this->actingAs($incomingManager)
            ->get(route('import-templates.incoming-letters.xlsx'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($outgoingManager)
            ->get(route('import-templates.outgoing-letters.xlsx'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($dispositionManager)
            ->get(route('import-templates.dispositions.xlsx'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($outgoingManager)
            ->get(route('import-templates.letter-number-reservations.xlsx'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_user_without_required_permission_cannot_download_templates(): void
    {
        $user = User::query()->where('email', 'dosen@esurat.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('import-templates.incoming-letters.xlsx'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('import-templates.outgoing-letters.xlsx'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('import-templates.dispositions.xlsx'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('import-templates.letter-number-reservations.xlsx'))
            ->assertForbidden();
    }

    private function makeUser(string $role, string $unitCode, string $positionName): User
    {
        $unit = Unit::query()->where('kode', $unitCode)->firstOrFail();
        $position = Position::query()->where('nama', $positionName)->firstOrFail();
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
