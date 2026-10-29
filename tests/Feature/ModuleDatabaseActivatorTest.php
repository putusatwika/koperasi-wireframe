<?php

namespace Tests\Feature;

use App\Support\DatabaseModuleActivator;
use Database\Seeders\ModuleStatusSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Module as ModuleInstance;
use Tests\TestCase;

class ModuleDatabaseActivatorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_modules_default_to_disabled_and_are_persisted_when_enabled(): void
    {
        $activator = $this->activator();

        $this->assertTrue($activator->hasStatus('NewModule', false));
        $this->assertDatabaseMissing('module_statuses', ['name' => 'NewModule']);

        $activator->setActiveByName('NewModule', true);

        $this->assertTrue($activator->hasStatus('NewModule', true));
        $this->assertDatabaseHas('module_statuses', ['name' => 'NewModule', 'enabled' => true]);
    }

    public function test_module_statuses_can_be_disabled_deleted_and_reset(): void
    {
        $activator = $this->activator();
        $module = Module::findOrFail('ExampleModule');

        $activator->enable($module);
        $this->assertTrue($activator->hasStatus($module, true));

        $activator->disable($module);
        $this->assertTrue($activator->hasStatus($module, false));

        $activator->delete($module);
        $this->assertDatabaseMissing('module_statuses', ['name' => 'ExampleModule']);

        $activator->setActiveByName('NewModule', true);
        $activator->reset();
        $this->assertDatabaseCount('module_statuses', 0);
    }

    public function test_seeder_synchronizes_new_and_removed_modules_without_overwriting_existing_statuses(): void
    {
        $existingModule = $this->createMock(ModuleInstance::class);
        $existingModule->method('getName')->willReturn('Example');

        $newModule = $this->createMock(ModuleInstance::class);
        $newModule->method('getName')->willReturn('NewModule');

        $modules = $this->createMock(RepositoryInterface::class);
        $modules->method('all')->willReturn([$existingModule, $newModule]);
        $this->app->instance(RepositoryInterface::class, $modules);

        $this->activator()->setActiveByName('Example', true);
        $this->activator()->setActiveByName('RemovedModule', true);

        $this->seed(ModuleStatusSeeder::class);
        $this->seed(ModuleStatusSeeder::class);

        $this->assertDatabaseHas('module_statuses', ['name' => 'Example', 'enabled' => true]);
        $this->assertDatabaseHas('module_statuses', ['name' => 'NewModule', 'enabled' => false]);
        $this->assertDatabaseMissing('module_statuses', ['name' => 'RemovedModule']);
        $this->assertDatabaseCount('module_statuses', 2);
    }

    private function activator(): DatabaseModuleActivator
    {
        $activator = app(ActivatorInterface::class);

        $this->assertInstanceOf(DatabaseModuleActivator::class, $activator);

        return $activator;
    }
}
