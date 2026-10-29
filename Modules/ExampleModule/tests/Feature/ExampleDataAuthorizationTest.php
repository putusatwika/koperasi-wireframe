<?php

namespace Modules\ExampleModule\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\ExampleModule\Filament\Resources\ExampleData\ExampleDataResource;
use Modules\ExampleModule\Models\ExampleData;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExampleDataAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_deleted_delete_permission_blocks_soft_delete_action(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo($this->permissions('view', 'update'));

        $deletePermission = Permission::findOrCreate('examplemodule.example_data.delete');
        $deletePermission->delete();

        $record = new ExampleData;

        $this->actingAs($user);
        $this->assertFalse(ExampleDataResource::canDelete($record));
        $this->assertFalse(ExampleDataResource::canDeleteAny());
        $this->assertFalse(ExampleDataResource::canForceDelete($record));
        $this->assertFalse(ExampleDataResource::canForceDeleteAny());
    }

    public function test_deleted_delete_permission_blocks_force_delete_action(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo($this->permissions('view', 'update'));

        $deletePermission = Permission::findOrCreate('examplemodule.example_data.delete');
        $deletePermission->delete();

        $this->actingAs($user);
        $record = new ExampleData;

        $this->assertFalse(ExampleDataResource::canForceDelete($record));
        $this->assertFalse(ExampleDataResource::canForceDeleteAny());
    }

    /**
     * @return array<int, Permission>
     */
    private function permissions(string ...$names): array
    {
        return collect($names)
            ->map(fn (string $name): Permission => Permission::findOrCreate("examplemodule.example_data.{$name}"))
            ->all();
    }
}
