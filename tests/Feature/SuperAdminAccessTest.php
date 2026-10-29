<?php

namespace Tests\Feature;

use App\Filament\Pages\ModuleManager;
use App\Filament\Resources\ArsipBackups\ArsipBackupResource;
use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_seeder_creates_the_default_account(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $superAdmin = User::query()->where('email', 'super@koperasi.id')->sole();

        $this->assertTrue(Hash::check('password', $superAdmin->password));
        $this->assertTrue($superAdmin->hasRole('super_admin'));
    }

    public function test_super_admin_can_log_in_with_the_seeded_credentials(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $superAdmin = User::query()->where('email', 'super@koperasi.id')->sole();
        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'super@koperasi.id',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_super_admin_cannot_log_in_with_an_incorrect_password(): void
    {
        $this->seed(SuperAdminSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'super@koperasi.id',
                'password' => 'incorrect-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_super_admin_bypasses_permission_checks_and_can_manage_modules(): void
    {
        DB::table('module_statuses')->insert([
            'name' => 'ExampleModule',
            'enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::findOrCreate('super_admin'));

        $this->assertTrue(Gate::forUser($superAdmin)->allows('permission-that-does-not-exist'));
        $this->actingAs($superAdmin)
            ->get(ModuleManager::getUrl(panel: 'user'))
            ->assertOk()
            ->assertSee('Manajemen Modul')
            ->assertSee('Refresh Modul')
            ->assertDontSee('Modul Terpasang')
            ->assertSee('Modul Belum Terpasang (1)')
            ->assertSee('Belum ada deskripsi untuk modul ini.')
            ->assertSee('Lihat Modul')
            ->assertSee("mountAction('viewModule'", escape: false)
            ->assertDontSee('@js(', escape: false)
            ->assertDontSee('wire:confirm', escape: false)
            ->assertSee('gap: 1.5rem', escape: false)
            ->assertSee('md:fi-grid-cols', escape: false)
            ->assertSee('xl:fi-grid-cols', escape: false);

        $this->actingAs($superAdmin)
            ->get(ArsipBackupResource::getUrl(panel: 'user'))
            ->assertOk()
            ->assertSee('Arsip Backup');

        Filament::setCurrentPanel(Filament::getPanel('user'));

        $moduleManager = Livewire::actingAs($superAdmin)
            ->test(ModuleManager::class)
            ->callAction('refreshModules')
            ->assertNotified();

        $this->assertDatabaseCount('module_statuses', count(Module::all()));

        $moduleManager
            ->mountAction('viewModule', ['moduleName' => 'ExampleModule'])
            ->assertActionMounted('viewModule')
            ->unmountAction()
            ->mountAction('disableModule', ['moduleName' => 'ExampleModule'])
            ->assertActionMounted('disableModule');
    }

    public function test_non_super_admin_cannot_manage_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(ModuleManager::getUrl(panel: 'user'))->assertForbidden();
    }

    public function test_active_users_can_log_in_and_open_the_shared_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('petugas'));
        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
        $response = $this->get('/')->assertOk();

        foreach (['module-manager', 'arsip-backups', 'users', 'roles', 'permissions'] as $path) {
            $response->assertDontSee('href="'.url($path).'"', escape: false);
            $this->get('/'.$path)->assertForbidden();
        }
    }

    public function test_system_resource_actions_are_denied_to_other_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ([
            UserResource::class,
            RoleResource::class,
            PermissionResource::class,
            ArsipBackupResource::class,
        ] as $resource) {
            foreach (['viewAny', 'create', 'update', 'delete', 'deleteAny'] as $action) {
                $this->assertFalse($resource::getAuthorizationResponse($action)->allowed());
            }
        }
    }

    public function test_inactive_users_cannot_enter_the_shared_panel(): void
    {
        foreach (['suspended', 'deactivated'] as $status) {
            $user = User::factory()->create(['status' => $status]);
            $user->assignRole(Role::findOrCreate('super_admin'));
            $this->actingAs($user)->get('/')->assertForbidden();
        }
    }

    public function test_super_admin_sees_and_can_open_all_system_screens(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin'));
        $response = $this->actingAs($user)->get('/')->assertOk();

        foreach (['module-manager', 'arsip-backups', 'users', 'roles', 'permissions'] as $path) {
            $response->assertSee('href="'.url($path).'"', escape: false);
            $this->get('/'.$path)->assertOk();
        }
    }

    public function test_dashboard_does_not_register_the_removed_summary_widget(): void
    {
        $widgets = Filament::getPanel('user')->getWidgets();

        $this->assertNotContains('App\Filament\Widgets\RingkasanKoperasi', $widgets);
    }
}
