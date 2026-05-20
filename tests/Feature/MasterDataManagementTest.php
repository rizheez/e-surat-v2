<?php

namespace Tests\Feature;

use App\Models\LetterCategory;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_manager_can_create_and_update_units_including_cross_unit_scope(): void
    {
        $manager = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $parent = Unit::query()->where('kode', 'RKT')->firstOrFail();

        $this->actingAs($manager)->post(route('master-data.units.store'), [
            'nama' => 'Unit Layanan Baru',
            'kode' => 'ULB',
            'parent_id' => $parent->id,
            'is_cross_unit_target' => true,
        ])->assertSessionHas('success');

        $unit = Unit::query()->where('kode', 'ULB')->firstOrFail();

        $this->assertTrue($unit->is_cross_unit_target);
        $this->assertSame($parent->id, $unit->parent_id);

        $this->actingAs($manager)->patch(route('master-data.units.update', $unit), [
            'nama' => 'Unit Layanan Baru Revisi',
            'kode' => 'ULB',
            'parent_id' => $unit->id,
            'is_cross_unit_target' => false,
        ])->assertSessionHas('error');

        $this->actingAs($manager)->patch(route('master-data.units.update', $unit), [
            'nama' => 'Unit Layanan Baru Revisi',
            'kode' => 'ULB',
            'parent_id' => $parent->id,
            'is_cross_unit_target' => false,
        ])->assertSessionHas('success');

        $unit->refresh();

        $this->assertSame('Unit Layanan Baru Revisi', $unit->nama);
        $this->assertFalse($unit->is_cross_unit_target);
    }

    public function test_manager_can_create_update_and_delete_letter_categories(): void
    {
        $manager = $this->makeUser('super-admin', 'RKT', 'Rektor');

        $this->actingAs($manager)->post(route('master-data.categories.store'), [
            'nama' => 'Kategori Uji',
            'kode' => 'KAT-UJI',
            'deskripsi' => 'Kategori untuk pengujian.',
        ])->assertSessionHas('success');

        $category = LetterCategory::query()->where('kode', 'KAT-UJI')->firstOrFail();

        $this->actingAs($manager)->patch(route('master-data.categories.update', $category), [
            'nama' => 'Kategori Uji Revisi',
            'kode' => 'KAT-UJI',
            'deskripsi' => 'Kategori revisi untuk pengujian.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('letter_categories', [
            'id' => $category->id,
            'nama' => 'Kategori Uji Revisi',
        ]);

        $this->actingAs($manager)
            ->delete(route('master-data.categories.destroy', $category))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('letter_categories', ['id' => $category->id]);
    }

    public function test_non_managers_cannot_access_master_data_routes(): void
    {
        $user = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan');

        $this->actingAs($user)
            ->get(route('master-data.units.index'))
            ->assertForbidden();
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
