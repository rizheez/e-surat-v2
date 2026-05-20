<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_user_manager_can_create_a_user_with_role_unit_and_position(): void
    {
        $manager = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $unit = Unit::query()->where('kode', 'FT')->firstOrFail();
        $position = Position::query()->firstOrCreate(
            ['nama' => 'Kaprodi Teknik Informatika', 'unit_id' => $unit->id],
            ['level' => 4],
        );

        $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'User Baru',
            'email' => 'user-baru@example.com',
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'role' => 'dosen',
            'is_active' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'user-baru@example.com')->firstOrFail();

        $this->assertSame($unit->id, $user->unit_id);
        $this->assertSame($position->id, $user->position_id);
        $this->assertTrue($user->hasRole('dosen'));
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_manager_cannot_disable_or_delete_their_own_account(): void
    {
        $manager = $this->makeUser('super-admin', 'RKT', 'Rektor');

        $this->actingAs($manager)
            ->from(route('users.index'))
            ->patch(route('users.status', $manager))
            ->assertRedirect(route('users.index'));

        $this->assertTrue($manager->fresh()->is_active);

        $this->actingAs($manager)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $manager))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $manager->id]);
    }

    public function test_user_manager_can_reset_another_users_password(): void
    {
        $manager = $this->makeUser('super-admin', 'RKT', 'Rektor');
        $target = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan');
        $oldHash = $target->password;

        $this->actingAs($manager)
            ->post(route('users.reset-password', $target))
            ->assertSessionHas('success');

        $target->refresh();

        $this->assertNotSame($oldHash, $target->password);
        $this->assertNotNull($target->remember_token);
    }

    public function test_non_managers_cannot_access_user_management_routes(): void
    {
        $user = $this->makeUser('tendik', 'FT', 'Tenaga Kependidikan');

        $this->actingAs($user)
            ->get(route('users.index'))
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
