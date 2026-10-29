<?php

namespace Tests\Feature;

use App\Filament\Pages\ModuleManager;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Providers\Filament\UserPanelProvider;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\ExampleModule\Filament\Resources\ExampleData\ExampleDataResource;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleNavigationOrganizerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_main_panel_registers_enabled_module_screens_without_custom_grouping(): void
    {
        $module = Module::findOrFail('ExampleModule');
        $module->enable();

        $panel = (new UserPanelProvider($this->app))->panel(Panel::make());
        $panel->boot();

        $this->assertTrue($panel->hasPlugin('modules'));
        $this->assertContains(ExampleDataResource::class, $panel->getResources());
        $this->assertSame('Example Module', ExampleDataResource::getNavigationGroup());
        $this->assertNull(ExampleDataResource::getNavigationParentItem());
        $this->assertContains(ModuleManager::class, $panel->getPages());
        $this->assertContains(UserResource::class, $panel->getResources());
        $this->assertContains(Dashboard::class, $panel->getPages());
    }

    public function test_disabled_module_screens_are_not_registered(): void
    {
        Module::findOrFail('ExampleModule')->disable();
        Module::findOrFail('Keanggotaan')->disable();

        $panel = (new UserPanelProvider($this->app))->panel(Panel::make());

        $this->assertNotContains(ExampleDataResource::class, $panel->getResources());
        $this->assertNotContains(AnggotaResource::class, $panel->getResources());
        $this->assertSame([], $panel->getClusters());
    }

    public function test_anggota_resource_access_requires_its_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertFalse(AnggotaResource::canViewAny());

        $user->givePermissionTo(Permission::findOrCreate('keanggotaan.anggota.viewAny'));

        $this->assertTrue(AnggotaResource::canViewAny());
        $this->assertFalse(ModuleManager::canAccess());
        $this->assertFalse(UserResource::canAccess());
    }
}
