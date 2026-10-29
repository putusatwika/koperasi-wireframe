<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\ExampleModule\Models\ExampleData;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class InstallModuleCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_rejects_an_unknown_module(): void
    {
        $this->artisan('module:install', ['module' => 'Unknown'])
            ->expectsOutputToContain('Module [Unknown] does not exist.')
            ->assertFailed();
    }

    public function test_it_runs_the_installation_command_owned_by_the_module(): void
    {
        $this->artisan('module:install', ['module' => 'ExampleModule'])
            ->expectsOutputToContain('ExampleModule module installed successfully.')
            ->assertSuccessful();

        $this->assertTrue(Module::findOrFail('ExampleModule')->isEnabled());

        $example = ExampleData::query()->where('name', 'Example Data 1')->first();

        $this->assertNotNull($example);
        $this->assertModelExists($example);
    }

    public function test_removed_legacy_workflow_module_is_not_available(): void
    {
        $this->artisan('module:install', ['module' => 'Deposito'])
            ->expectsOutputToContain('Module [Deposito] does not exist.')
            ->assertFailed();
    }

    public function test_backup_is_a_core_system_feature_instead_of_a_module(): void
    {
        $this->artisan('module:install', ['module' => 'Backup'])
            ->expectsOutputToContain('Module [Backup] does not exist.')
            ->assertFailed();
    }
}
