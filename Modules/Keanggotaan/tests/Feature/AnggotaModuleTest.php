<?php

namespace Modules\Keanggotaan\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Keanggotaan\Database\Seeders\AnggotaSeeder;
use Modules\Keanggotaan\Events\AnggotaTerdaftar;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Keanggotaan\Services\AnggotaService;
use Modules\Keanggotaan\Tests\Fixtures\AnggotaFormHarness;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnggotaModuleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_anggota_uses_the_module_table_and_user_relationship(): void
    {
        $anggota = new Anggota;

        $this->assertSame('keanggotaan_anggotas', $anggota->getTable());
        $this->assertInstanceOf(User::class, $anggota->user()->getRelated());
        $this->assertContains('account_number', $anggota->getFillable());
        $this->assertSame('active', $anggota->status);
    }

    public function test_anggota_resource_requires_permissions(): void
    {
        $user = User::factory()->create();
        $record = new Anggota;

        $this->actingAs($user);

        $this->assertFalse(AnggotaResource::canViewAny());
        $this->assertFalse(AnggotaResource::canView($record));
        $this->assertFalse(AnggotaResource::canCreate());
        $this->assertFalse(AnggotaResource::canEdit($record));
        $this->assertFalse(AnggotaResource::canDelete($record));

        $user->givePermissionTo([
            Permission::findOrCreate('keanggotaan.anggota.viewAny'),
            Permission::findOrCreate('keanggotaan.anggota.view'),
            Permission::findOrCreate('keanggotaan.anggota.create'),
            Permission::findOrCreate('keanggotaan.anggota.update'),
            Permission::findOrCreate('keanggotaan.anggota.delete'),
        ]);

        $this->assertTrue(AnggotaResource::canViewAny());
        $this->assertTrue(AnggotaResource::canView($record));
        $this->assertTrue(AnggotaResource::canCreate());
        $this->assertTrue(AnggotaResource::canEdit($record));
        $this->assertTrue(AnggotaResource::canDelete($record));
    }

    public function test_anggota_module_no_longer_registers_portal_permission(): void
    {
        $this->assertNotContains('keanggotaan.portal_anggota.view', config('keanggotaan.permissions', []));
    }

    public function test_anggota_seeder_does_not_create_member_data_for_super_admin(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        $superAdmin = User::factory()->create(['email' => 'super@koperasi.id']);
        $superAdmin->assignRole(Role::findOrCreate('super_admin'));

        (new AnggotaSeeder)->run();

        $this->assertNull($superAdmin->fresh()->anggota);
    }

    public function test_anggota_registration_creates_user_and_member_data_together(): void
    {
        User::query()->count();
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        $anggota = app(AnggotaService::class)->create([
            'user' => [
                'name' => 'Anggota Satu',
                'email' => 'anggota@example.test',
                'password' => 'password123',
            ],
            'member_number' => 'AGT-00001',
            'account_number' => '1234567890',
            'identity_number' => '1234567890123456',
            'phone' => '081234567890',
            'address' => 'Alamat anggota',
            'joined_at' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->assertModelExists($anggota);
        $this->assertModelExists($anggota->user);
        $this->assertSame('anggota@example.test', $anggota->user->email);
        $this->assertTrue(Hash::check('password123', $anggota->user->password));
        $this->assertTrue($anggota->user->hasRole('anggota'));
        $this->assertSame('active', $anggota->status);
    }

    public function test_member_number_is_generated_and_the_requested_status_is_preserved(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        $profileMigration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_29_170707_add_bank_and_profile_fields_to_anggotas_table.php');
        $profileMigration->up();

        Anggota::factory()->create(['member_number' => 'AGT-00001']);

        $anggota = app(AnggotaService::class)->create([
            'user' => [
                'name' => 'Anggota Baru',
                'email' => 'anggota.baru@example.test',
                'password' => 'password123',
            ],
            'account_number' => '9876543210',
            'bank_name' => 'BCA',
            'identity_number' => '3273010101010001',
            'occupation' => 'Karyawan',
            'income' => 7500000,
            'joined_at' => now(),
            'status' => 'active',
        ]);

        $this->assertSame('AGT-00002', $anggota->member_number);
        $this->assertSame('active', $anggota->status);
        $this->assertSame('BCA', $anggota->bank_name);
        $this->assertSame('Karyawan', $anggota->occupation);
        $this->assertSame('7500000.00', $anggota->income);
    }

    public function test_initial_savings_input_is_forwarded_to_the_registration_event(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        Event::fake([AnggotaTerdaftar::class]);

        $anggota = app(AnggotaService::class)->create([
            'user' => [
                'name' => 'Anggota Simpanan',
                'email' => 'anggota.simpanan@example.test',
                'password' => 'password123',
            ],
            'account_number' => '1112223334',
            'identity_number' => '3273010101018888',
            'joined_at' => now()->toDateString(),
            'status' => 'active',
            'initial_savings' => [
                'pokok_enabled' => true,
                'pokok' => 100000,
                'wajib_enabled' => true,
                'wajib' => 50000,
            ],
        ]);

        Event::assertDispatched(
            AnggotaTerdaftar::class,
            fn (AnggotaTerdaftar $event): bool => $event->anggota->is($anggota)
                && $event->initialSavings === ['pokok' => 100000.0, 'wajib' => 50000.0],
        );
    }

    public function test_initial_savings_input_is_ignored_when_not_enabled(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        Event::fake([AnggotaTerdaftar::class]);

        app(AnggotaService::class)->create([
            'user' => [
                'name' => 'Anggota Tanpa Setoran',
                'email' => 'anggota.tanpa.setoran@example.test',
                'password' => 'password123',
            ],
            'account_number' => '1112223335',
            'identity_number' => '3273010101018889',
            'joined_at' => now()->toDateString(),
            'status' => 'active',
            'initial_savings' => [
                'pokok_enabled' => false,
                'pokok' => 100000,
                'wajib_enabled' => false,
                'wajib' => 50000,
            ],
        ]);

        Event::assertDispatched(
            AnggotaTerdaftar::class,
            fn (AnggotaTerdaftar $event): bool => $event->initialSavings === [],
        );
    }

    public function test_initial_savings_can_be_paid_for_only_one_category(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        Event::fake([AnggotaTerdaftar::class]);

        app(AnggotaService::class)->create([
            'user' => [
                'name' => 'Anggota Pokok Saja',
                'email' => 'anggota.pokok.saja@example.test',
                'password' => 'password123',
            ],
            'account_number' => '1112223336',
            'identity_number' => '3273010101018890',
            'joined_at' => now()->toDateString(),
            'status' => 'pending',
            'initial_savings' => [
                'pokok_enabled' => true,
                'pokok' => 100000,
                'wajib_enabled' => false,
                'wajib' => 50000,
            ],
        ]);

        Event::assertDispatched(
            AnggotaTerdaftar::class,
            fn (AnggotaTerdaftar $event): bool => $event->initialSavings === ['pokok' => 100000.0],
        );
    }

    public function test_duplicate_account_number_is_reported_as_a_friendly_validation_error(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        Anggota::factory()->create(['account_number' => '1234567890']);

        try {
            app(AnggotaService::class)->create([
                'user' => [
                    'name' => 'Anggota Duplikat',
                    'email' => 'duplikat@example.test',
                    'password' => 'password123',
                ],
                'account_number' => '1234567890',
                'identity_number' => '3273010101019999',
                'joined_at' => now()->toDateString(),
                'status' => 'active',
            ]);

            $this->fail('Pendaftaran dengan nomor rekening duplikat seharusnya gagal.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Nomor rekening ini sudah dipakai oleh anggota lain. Periksa kembali nomor rekening atau gunakan nomor yang berbeda.',
                $exception->errors()['data.account_number'][0] ?? null,
            );
        }

        $this->assertDatabaseMissing('users', ['email' => 'duplikat@example.test']);
    }

    public function test_editing_a_member_does_not_treat_its_own_email_as_duplicate(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        $anggota = Anggota::factory()->create();

        Livewire::test(AnggotaFormHarness::class, ['recordId' => $anggota->id])
            ->fillForm([
                'bank_name' => 'BCA',
                'user.email' => $anggota->user->email,
            ])
            ->call('validateForm')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $anggota->user_id,
            'email' => $anggota->user->email,
        ]);
    }

    public function test_super_admin_is_hidden_from_the_anggota_resource_query(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        $superAdmin = User::factory()->create(['email' => 'super@koperasi.id']);
        $superAdmin->assignRole(Role::findOrCreate('super_admin'));
        $superAdminAnggota = Anggota::factory()->create(['user_id' => $superAdmin->getKey()]);

        $member = Anggota::factory()->create();

        $ids = AnggotaResource::getEloquentQuery()
            ->withoutGlobalScopes()
            ->pluck('id');

        $this->assertFalse($ids->contains($superAdminAnggota->getKey()));
        $this->assertTrue($ids->contains($member->getKey()));
    }
}
