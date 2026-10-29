<?php

namespace Tests\Feature;

use App\Filament\Resources\AccountTypes\AccountTypeResource;
use App\Filament\Resources\CoaAccounts\CoaAccountResource;
use App\Filament\Resources\Jurnals\JurnalResource;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CoreAccountingResourceAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_core_accounting_resources_are_restricted_to_super_admins(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        foreach ($this->resources() as $resource) {
            $this->assertFalse($resource::canAccess());
        }

        $user->assignRole(Role::findOrCreate('super_admin'));

        foreach ($this->resources() as $resource) {
            $this->assertTrue($resource::canAccess());
        }
    }

    /**
     * @return array<int, class-string>
     */
    private function resources(): array
    {
        return [
            AccountTypeResource::class,
            CoaAccountResource::class,
            JurnalResource::class,
        ];
    }
}
