<?php

namespace Modules\Peminjaman\Tests\Feature;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema as FilamentSchema;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Peminjaman\Database\Seeders\JenisPinjamanSeeder;
use Modules\Peminjaman\Database\Seeders\PeminjamanRoleSeeder;
use Modules\Peminjaman\Database\Seeders\PeminjamanUserSeeder;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjaman;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjamanAnggota;
use Modules\Peminjaman\Filament\Pages\SimulasiPeminjaman;
use Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\DaftarPeminjamanResource;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\JenisPinjamanResource;
use Modules\Peminjaman\Filament\Resources\PemeriksaanPeminjamans\PemeriksaanPeminjamanResource;
use Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas\PeminjamanForm;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\PengajuanPeminjamanResource;
use Modules\Peminjaman\Filament\Resources\PersetujuanPencairans\PersetujuanPencairanResource;
use Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\SimulasiPeminjamanResource;
use Modules\Peminjaman\Models\JenisPinjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Models\SimulasiPeminjaman as SimulasiPeminjamanModel;
use Modules\Peminjaman\Services\PeminjamanService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeminjamanModuleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('peminjaman', require base_path('Modules/Peminjaman/config/config.php'));

        if (! Schema::hasTable('keanggotaan_anggotas')) {
            $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
            $migration->up();
        }

        if (! Schema::hasTable('peminjaman_peminjaman')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_10_060258_create_peminjamen_table.php');
            $migration->up();
        }

        if (! Schema::hasColumn('peminjaman_peminjaman', 'monthly_income')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_20_000001_add_personal_application_details_to_peminjaman_table.php');
            $migration->up();
        }

        if (! Schema::hasTable('peminjaman_approval_workflows')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_11_000002_create_peminjaman_approval_workflows_tables.php');
            $migration->up();
        }

        if (! Schema::hasTable('peminjaman_jenis_pinjaman')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_12_000001_create_jenis_pinjaman_table.php');
            $migration->up();
        }

        if (! Schema::hasTable('peminjaman_simulasi_peminjaman')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_12_000002_create_simulasi_peminjaman_table.php');
            $migration->up();
        }

        if (! Schema::hasTable('peminjaman_jadwal_angsuran')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_27_000001_create_peminjaman_jadwal_angsuran_table.php');
            $migration->up();
        }

        if (! Schema::hasColumn('peminjaman_jenis_pinjaman', 'biaya_administrasi')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_27_000002_add_fee_and_penalty_to_jenis_pinjaman_table.php');
            $migration->up();
        }

        if (! Schema::hasColumn('peminjaman_peminjaman', 'jenis_pinjaman_id')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_27_000003_add_fee_and_penalty_to_peminjaman_table.php');
            $migration->up();
        }

        if (! Schema::hasColumn('peminjaman_peminjaman', 'lunas_at')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_27_000004_add_lunas_tracking_to_peminjaman_table.php');
            $migration->up();
        }

        if (! Schema::hasColumn('peminjaman_jadwal_angsuran', 'denda_dibayar')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_27_000005_add_denda_dibayar_to_jadwal_angsuran_table.php');
            $migration->up();
        }

        if (! Schema::hasColumn('peminjaman_peminjaman', 'rekomendasi')) {
            $migration = require base_path('Modules/Peminjaman/database/migrations/2026_10_28_000004_add_rekomendasi_to_peminjaman_table.php');
            $migration->up();
        }

    }

    public function test_peminjaman_uses_the_module_table(): void
    {
        $peminjaman = new Peminjaman;

        $this->assertSame('peminjaman_peminjaman', $peminjaman->getTable());
        $this->assertSame(StatusPeminjaman::Submitted, $peminjaman->status);
    }

    public function test_peminjaman_role_seeder_registers_idempotent_workflow_roles_and_permissions(): void
    {
        (new PeminjamanRoleSeeder)->run();
        (new PeminjamanRoleSeeder)->run();

        $bendahara = Role::findByName('bendahara');
        $ketua = Role::findByName('ketua');

        $this->assertTrue($bendahara->hasPermissionTo('peminjaman.pengajuan.approve_bendahara'));
        $this->assertTrue($bendahara->hasPermissionTo('peminjaman.pemeriksaan.process'));
        $this->assertTrue($bendahara->hasPermissionTo('peminjaman.persetujuan.disburse'));
        $this->assertFalse($bendahara->hasPermissionTo('peminjaman.persetujuan.approve_ketua'));

        $this->assertTrue($ketua->hasPermissionTo('peminjaman.persetujuan.approve_ketua'));
        $this->assertTrue($ketua->hasPermissionTo('peminjaman.persetujuan.reject'));
        $this->assertFalse($ketua->hasPermissionTo('peminjaman.pemeriksaan.process'));

        $this->assertSame(1, Role::query()->where('name', 'bendahara')->count());
        $this->assertSame(1, Role::query()->where('name', 'ketua')->count());
        $this->assertModelExists(Permission::findByName('peminjaman.persetujuan.disburse'));
    }

    public function test_anggota_role_only_receives_member_application_permissions(): void
    {
        (new PeminjamanRoleSeeder)->run();

        $anggota = Role::findByName('anggota');

        $this->assertTrue($anggota->hasPermissionTo('peminjaman.simulasi.view'));
        $this->assertTrue($anggota->hasPermissionTo('peminjaman.pengajuan.create'));
        $this->assertTrue($anggota->hasPermissionTo('peminjaman.pengajuan.view'));
        $this->assertTrue($anggota->hasPermissionTo('peminjaman.pengajuan.update'));
        $this->assertTrue($anggota->hasPermissionTo('peminjaman.pengajuan.delete'));
        $this->assertTrue($anggota->hasPermissionTo('peminjaman.pengajuan.cancel'));
        $this->assertFalse($anggota->hasPermissionTo('peminjaman.pengajuan.viewAny'));
        $this->assertFalse($anggota->hasPermissionTo('peminjaman.pemeriksaan.process'));
        $this->assertFalse($anggota->hasPermissionTo('peminjaman.persetujuan.approve_ketua'));
    }

    public function test_peminjaman_user_seeder_connects_users_to_workflow_roles(): void
    {
        (new PeminjamanRoleSeeder)->run();
        (new PeminjamanUserSeeder)->run();
        (new PeminjamanUserSeeder)->run();

        $bendahara = User::query()->where('email', 'bendahara@koperasi.id')->firstOrFail();
        $ketua = User::query()->where('email', 'ketua@koperasi.id')->firstOrFail();

        $this->assertTrue($bendahara->hasRole('bendahara'));
        $this->assertTrue($ketua->hasRole('ketua'));
        $this->assertSame(1, User::query()->where('email', 'bendahara@koperasi.id')->count());
        $this->assertSame(1, User::query()->where('email', 'ketua@koperasi.id')->count());
    }

    public function test_member_selector_remains_visible_when_member_is_prefilled(): void
    {
        $schema = PeminjamanForm::configure(FilamentSchema::make(), true, 123);
        $memberField = $schema->getComponents()[0];

        $this->assertInstanceOf(Select::class, $memberField);
        $this->assertTrue($memberField->isDisabled());
    }

    public function test_jenis_pinjaman_uses_an_auto_generated_uuid_and_bunga_enum(): void
    {
        $jenisPinjaman = JenisPinjaman::query()->create([
            'kode' => 'KONSUMTIF',
            'nama' => 'Pinjaman Konsumtif',
            'metode_bunga' => JenisBungaPeminjaman::Flat,
            'persentase_bunga_per_bulan' => 1.25,
        ]);

        $this->assertSame('peminjaman_jenis_pinjaman', $jenisPinjaman->getTable());
        $this->assertFalse($jenisPinjaman->incrementing);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $jenisPinjaman->getKey());
        $this->assertSame(JenisBungaPeminjaman::Flat, $jenisPinjaman->metode_bunga);
        $this->assertModelExists($jenisPinjaman);
    }

    public function test_jenis_pinjaman_resource_requires_crud_permissions(): void
    {
        $user = User::factory()->create();
        $jenisPinjaman = new JenisPinjaman;

        $this->actingAs($user);
        $this->assertFalse(JenisPinjamanResource::canViewAny());
        $this->assertFalse(JenisPinjamanResource::canView($jenisPinjaman));
        $this->assertFalse(JenisPinjamanResource::canCreate());
        $this->assertFalse(JenisPinjamanResource::canEdit($jenisPinjaman));
        $this->assertFalse(JenisPinjamanResource::canDelete($jenisPinjaman));

        $user->givePermissionTo([
            Permission::findOrCreate('peminjaman.jenis_pinjaman.viewAny'),
            Permission::findOrCreate('peminjaman.jenis_pinjaman.view'),
            Permission::findOrCreate('peminjaman.jenis_pinjaman.create'),
            Permission::findOrCreate('peminjaman.jenis_pinjaman.update'),
            Permission::findOrCreate('peminjaman.jenis_pinjaman.delete'),
        ]);

        $this->assertTrue(JenisPinjamanResource::canViewAny());
        $this->assertTrue(JenisPinjamanResource::canView($jenisPinjaman));
        $this->assertTrue(JenisPinjamanResource::canCreate());
        $this->assertTrue(JenisPinjamanResource::canEdit($jenisPinjaman));
        $this->assertTrue(JenisPinjamanResource::canDelete($jenisPinjaman));
    }

    public function test_simulasi_peminjaman_resource_requires_view_permissions(): void
    {
        $user = User::factory()->create();
        $simulasi = new SimulasiPeminjamanModel;

        $this->actingAs($user);
        $this->assertFalse(SimulasiPeminjamanResource::canViewAny());
        $this->assertFalse(SimulasiPeminjamanResource::canView($simulasi));

        $user->givePermissionTo([
            Permission::findOrCreate('peminjaman.simulasi_pinjaman.viewAny'),
            Permission::findOrCreate('peminjaman.simulasi_pinjaman.view'),
        ]);

        $this->assertTrue(SimulasiPeminjamanResource::canViewAny());
        $this->assertTrue(SimulasiPeminjamanResource::canView($simulasi));
    }

    public function test_jenis_pinjaman_seeder_creates_default_products_idempotently(): void
    {
        $seeder = new JenisPinjamanSeeder;

        $seeder->run();
        $seeder->run();

        $this->assertSame(4, JenisPinjaman::query()->count());
        $reguler = JenisPinjaman::query()->where('kode', 'PJ-RG')->firstOrFail();

        $this->assertSame(JenisBungaPeminjaman::Effective, $reguler->metode_bunga);
        $this->assertSame('1.1000', (string) $reguler->persentase_bunga_per_bulan);
    }

    public function test_simulasi_peminjaman_uses_the_module_table_and_casts_results(): void
    {
        $simulasi = new SimulasiPeminjamanModel;
        $simulasi->fill([
            'jenis_pinjaman_id' => '00000000-0000-0000-0000-000000000001',
            'jumlah_pinjaman' => 1000000,
            'metode_bunga' => JenisBungaPeminjaman::Effective,
            'persentase_bunga_per_bulan' => 1.10,
            'tenor_bulan' => 12,
            'peruntukan' => 'productive',
            'rencana_mulai_cicilan' => '2026-11-01',
        ]);

        $this->assertSame('peminjaman_simulasi_peminjaman', $simulasi->getTable());
        $this->assertSame(JenisBungaPeminjaman::Effective, $simulasi->metode_bunga);
        $this->assertSame('productive', $simulasi->peruntukan);
        $this->assertSame('2026-11-01', $simulasi->rencana_mulai_cicilan->toDateString());
        $this->assertContains('jumlah_pinjaman', $simulasi->getFillable());
        $this->assertContains('jenis_pinjaman_id', $simulasi->getFillable());
    }

    public function test_personal_application_defaults_to_the_latest_member_simulation(): void
    {
        $anggota = Anggota::factory()->create();
        SimulasiPeminjamanModel::query()->create([
            'anggota_id' => $anggota->getKey(),
            'jenis_pinjaman_id' => null,
            'jumlah_pinjaman' => 2500000,
            'metode_bunga' => JenisBungaPeminjaman::Effective,
            'persentase_bunga_per_bulan' => 1.25,
            'tenor_bulan' => 18,
            'peruntukan' => 'education',
            'rencana_mulai_cicilan' => '2026-12-01',
        ]);

        $schema = PeminjamanForm::configure(FilamentSchema::make(), false, $anggota->getKey());
        $fields = collect($schema->getComponents())->keyBy(fn ($component): string => $component->getName());

        $this->assertSame('effective', $fields->get('interest_type')->getDefaultState());
        $this->assertSame(15.0, $fields->get('interest_rate')->getDefaultState());
        $this->assertSame(18, $fields->get('term_months')->getDefaultState());
        $this->assertSame('2500000.00', $fields->get('loan_amount')->getDefaultState());
        $this->assertSame('education', $fields->get('purpose')->getDefaultState());
        $this->assertSame(15.0, $fields->get('interest_rate')->getStateToDehydrate(1.25)['interest_rate']);
    }

    public function test_default_simulation_shows_the_previous_simulation_as_a_disabled_reference(): void
    {
        $anggota = Anggota::factory()->create();
        $simulation = SimulasiPeminjamanModel::query()->create([
            'anggota_id' => $anggota->getKey(),
            'jenis_pinjaman_id' => null,
            'jumlah_pinjaman' => 2500000,
            'metode_bunga' => JenisBungaPeminjaman::Effective,
            'persentase_bunga_per_bulan' => 1.25,
            'tenor_bulan' => 18,
            'peruntukan' => 'education',
            'rencana_mulai_cicilan' => '2026-12-01',
        ]);

        $schema = PeminjamanForm::configure(FilamentSchema::make(), false, $anggota->getKey());
        $simulationField = collect($schema->getComponents())
            ->first(fn ($component): bool => $component->getName() === 'simulation_id');

        $this->assertNotNull($simulationField);
        $this->assertTrue($simulationField->isDisabled());
        $this->assertSame($simulation->getKey(), $simulationField->getDefaultState());
    }

    public function test_simulation_service_saves_data_without_member_context(): void
    {
        $this->actingAs(User::factory()->create());

        $simulasi = app(PeminjamanService::class)->createSimulation([
            'jenis_pinjaman_id' => 'custom',
            'jumlah_pinjaman' => 1000000,
            'metode_bunga' => JenisBungaPeminjaman::Flat,
            'persentase_bunga_per_bulan' => 0.90,
            'tenor_bulan' => 12,
            'peruntukan' => 'consumption',
            'rencana_mulai_cicilan' => '2026-11-01',
        ]);

        $this->assertNull($simulasi->anggota_id);
        $this->assertModelExists($simulasi);
    }

    public function test_simulation_supports_flat_interest(): void
    {
        $result = app(PeminjamanService::class)->simulate([
            'loan_amount' => 1000000,
            'interest_type' => 'flat',
            'interest_rate' => 12,
            'term_months' => 12,
        ]);

        $this->assertSame(93333.33, $result['monthly_payment']);
        $this->assertSame(120000.0, $result['total_interest']);
        $this->assertSame(1120000.0, $result['total_repayment']);
        $this->assertCount(12, $result['schedule']);
        $this->assertSame(10000.0, $result['schedule'][0]['interest']);
        $this->assertSame(10000.0, $result['schedule'][11]['interest']);
    }

    public function test_simulation_returns_declining_interest_for_effective_interest(): void
    {
        $result = app(PeminjamanService::class)->simulate([
            'loan_amount' => 1000000,
            'interest_type' => 'effective',
            'interest_rate' => 12,
            'term_months' => 12,
        ]);

        $this->assertCount(12, $result['schedule']);
        $this->assertGreaterThan($result['schedule'][11]['interest'], $result['schedule'][0]['interest']);
        $this->assertEquals(0.0, $result['schedule'][11]['ending_balance']);
    }

    public function test_simulation_returns_fixed_payment_for_annuity_interest(): void
    {
        $result = app(PeminjamanService::class)->simulate([
            'loan_amount' => 1000000,
            'interest_type' => 'annuity',
            'interest_rate' => 12,
            'term_months' => 12,
        ]);

        $this->assertCount(12, $result['schedule']);
        $this->assertSame($result['schedule'][0]['payment'], $result['schedule'][1]['payment']);
        $this->assertGreaterThan($result['schedule'][11]['interest'], $result['schedule'][0]['interest']);
        $this->assertEquals(0.0, $result['schedule'][11]['ending_balance']);
    }

    public function test_resource_and_simulation_page_require_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertFalse(PengajuanPeminjamanResource::canViewAny());
        $this->assertFalse(DaftarPeminjamanResource::canViewAny());
        $this->assertFalse(PemeriksaanPeminjamanResource::canViewAny());
        $this->assertFalse(PersetujuanPencairanResource::canViewAny());
        $this->assertFalse(AjukanPeminjaman::canAccess());
        $this->assertFalse(AjukanPeminjamanAnggota::canAccess());
        $this->assertFalse(SimulasiPeminjaman::canAccess());

        $user->givePermissionTo([
            Permission::findOrCreate('peminjaman.pengajuan.viewAny'),
            Permission::findOrCreate('peminjaman.daftar_peminjaman.viewAny'),
            Permission::findOrCreate('peminjaman.pemeriksaan.viewAny'),
            Permission::findOrCreate('peminjaman.persetujuan.viewAny'),
            Permission::findOrCreate('peminjaman.pengajuan.create'),
            Permission::findOrCreate('peminjaman.simulasi.view'),
        ]);

        $this->assertTrue(PengajuanPeminjamanResource::canViewAny());
        $this->assertTrue(DaftarPeminjamanResource::canViewAny());
        $this->assertTrue(PemeriksaanPeminjamanResource::canViewAny());
        $this->assertTrue(PersetujuanPencairanResource::canViewAny());
        $this->assertTrue(AjukanPeminjaman::canAccess());
        $this->assertFalse(AjukanPeminjamanAnggota::canAccess());
        $this->assertTrue(SimulasiPeminjaman::canAccess());

        $user->givePermissionTo(Permission::findOrCreate('peminjaman.pengajuan.create_any'));

        $this->assertTrue(PengajuanPeminjamanResource::canViewAny());
    }

    public function test_create_any_can_submit_for_another_member_but_not_for_self(): void
    {
        $user = User::factory()->create();
        $ownAnggota = Anggota::factory()->create(['user_id' => $user->getKey()]);
        $otherAnggota = Anggota::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('peminjaman.pengajuan.create_any'));
        $this->actingAs($user);

        $data = [
            'anggota_id' => $otherAnggota->getKey(),
            'account_number' => $otherAnggota->account_number,
            'interest_type' => 'flat',
            'interest_rate' => 12,
            'term_months' => 12,
            'loan_amount' => 1000000,
            'purpose' => 'productive',
        ];

        $peminjaman = app(PeminjamanService::class)->create($data, true);

        $this->assertSame($otherAnggota->getKey(), $peminjaman->anggota_id);
        $this->assertNotSame($ownAnggota->getKey(), $peminjaman->anggota_id);

        $this->expectException(\InvalidArgumentException::class);
        app(PeminjamanService::class)->create([...$data, 'anggota_id' => $ownAnggota->getKey()], true);
    }

    public function test_member_simulation_can_be_continued_to_a_member_draft(): void
    {
        $user = User::factory()->create();
        $member = Anggota::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('peminjaman.pengajuan.create_any'));
        $this->actingAs($user);

        $service = app(PeminjamanService::class);
        $simulationData = [
            'anggota_id' => $member->getKey(),
            'jenis_pinjaman_id' => 'custom',
            'jumlah_pinjaman' => 1000000,
            'metode_bunga' => JenisBungaPeminjaman::Flat,
            'persentase_bunga_per_bulan' => 0.9,
            'tenor_bulan' => 12,
            'peruntukan' => 'productive',
            'rencana_mulai_cicilan' => '2026-11-01',
        ];

        $simulation = $service->createSimulation($simulationData);
        $draft = $service->createDraft([
            'anggota_id' => $member->getKey(),
            'loan_amount' => 1000000,
            'interest_type' => 'flat',
            'interest_rate' => 10.8,
            'term_months' => 12,
            'purpose' => 'productive',
        ], true);

        $this->assertSame($member->getKey(), $simulation->fresh()->anggota_id);
        $this->assertSame($member->getKey(), $draft->anggota_id);
        $this->assertSame(StatusPeminjaman::Draft, $draft->status);
    }

    public function test_personal_application_form_contains_validated_application_details(): void
    {
        $schema = PeminjamanForm::configure(FilamentSchema::make(), false, 123);
        $fields = collect($schema->getComponents())->keyBy(fn ($component): string => $component->getName());

        foreach ([
            'monthly_income',
            'dependents_count',
            'urgency',
            'purpose_description',
            'need_description',
            'funds_needed_at',
            'repayment_source',
        ] as $field) {
            $this->assertTrue($fields->has($field), "Field {$field} tidak tersedia pada form pengajuan personal.");
            $this->assertTrue($fields->get($field)->isRequired(), "Field {$field} harus divalidasi sebagai wajib.");
        }

        $this->assertTrue($fields->get('anggota_id')->isDisabled());
    }

    public function test_personal_application_details_are_fillable_and_cast(): void
    {
        $peminjaman = new Peminjaman;
        $peminjaman->fill([
            'monthly_income' => 12500000,
            'dependents_count' => 3,
            'funds_needed_at' => '2026-11-15',
        ]);

        $this->assertContains('monthly_income', $peminjaman->getFillable());
        $this->assertSame('12500000.00', (string) $peminjaman->monthly_income);
        $this->assertSame(3, $peminjaman->dependents_count);
        $this->assertSame('2026-11-15', $peminjaman->funds_needed_at->toDateString());
    }

    public function test_default_simulation_fields_are_locked_and_custom_fields_are_editable(): void
    {
        $schema = PeminjamanForm::configure(FilamentSchema::make(), false, 123);
        $fields = collect($schema->getComponents())->keyBy(fn ($component): string => $component->getName());

        foreach (['interest_type', 'interest_rate', 'term_months', 'loan_amount', 'purpose'] as $field) {
            $this->assertTrue($fields->get($field)->isDehydrated());
        }

        $this->assertTrue($fields->get('simulation_type')->isLive());
    }

    public function test_personal_application_hides_system_managed_fields(): void
    {
        $schema = PeminjamanForm::configure(FilamentSchema::make(), false, 123);
        $fields = collect($schema->getComponents())->keyBy(fn ($component): string => $component->getName());

        foreach (['account_number', 'guarantee_required', 'penalty_rate', 'late_fee', 'approval_document', 'rejection_reason'] as $field) {
            $this->assertFalse($fields->get($field)?->isVisible() ?? false, "Field {$field} tidak boleh tampil pada pengajuan personal.");
        }
    }

    public function test_default_simulation_cannot_be_changed_during_application_update(): void
    {
        $peminjaman = Peminjaman::factory()->create([
            'simulation_type' => 'default',
            'loan_amount' => 1000000,
            'purpose' => 'productive',
        ]);

        app(PeminjamanService::class)->updateApplication($peminjaman, [
            'simulation_type' => 'default',
            'loan_amount' => 5000000,
            'purpose' => 'consumption',
        ]);

        $this->assertSame('1000000.00', (string) $peminjaman->fresh()->loan_amount);
        $this->assertSame('productive', $peminjaman->fresh()->purpose);
    }

    public function test_custom_simulation_can_be_changed_during_application_update(): void
    {
        $peminjaman = Peminjaman::factory()->create([
            'simulation_type' => 'default',
            'loan_amount' => 1000000,
            'purpose' => 'productive',
        ]);

        app(PeminjamanService::class)->updateApplication($peminjaman, [
            'simulation_type' => 'custom',
            'loan_amount' => 5000000,
            'purpose' => 'consumption',
        ]);

        $updated = $peminjaman->fresh();
        $this->assertSame('custom', $updated->simulation_type->value);
        $this->assertSame('5000000.00', (string) $updated->loan_amount);
        $this->assertSame('consumption', $updated->purpose);
    }

    public function test_simulation_can_be_saved_as_draft_then_submitted(): void
    {
        $user = User::factory()->create();
        $anggota = Anggota::factory()->create(['user_id' => $user->getKey()]);
        $user->givePermissionTo(Permission::findOrCreate('peminjaman.pengajuan.create'));
        $this->actingAs($user);

        $peminjaman = app(PeminjamanService::class)->createDraft([
            'interest_type' => 'flat',
            'interest_rate' => 12,
            'term_months' => 12,
            'loan_amount' => 1000000,
            'purpose' => 'productive',
        ]);

        $this->assertSame(StatusPeminjaman::Draft, $peminjaman->status);
        $this->assertSame($anggota->getKey(), $peminjaman->anggota_id);

        app(PeminjamanService::class)->submit($peminjaman);

        $this->assertSame(StatusPeminjaman::Submitted, $peminjaman->fresh()->status);
    }

    public function test_member_can_request_cancellation_and_bendahara_can_decide_it(): void
    {
        $user = User::factory()->create();
        Anggota::factory()->create(['user_id' => $user->getKey()]);
        $peminjaman = Peminjaman::factory()->create();
        $peminjaman->update(['anggota_id' => $user->anggota->getKey()]);
        $this->actingAs($user);

        app(PeminjamanService::class)->requestCancellation($peminjaman, 'Tidak jadi membutuhkan pinjaman.');

        $this->assertSame(StatusPeminjaman::CancellationRequested, $peminjaman->fresh()->status);

        app(PeminjamanService::class)->approveCancellation($peminjaman);

        $this->assertSame(StatusPeminjaman::Cancelled, $peminjaman->fresh()->status);
    }

    public function test_approval_workflow_can_have_an_additional_step_without_new_columns(): void
    {
        $originalWorkflow = config('peminjaman.approval_workflows');
        config()->set('peminjaman.approval_workflows.loan', [
            [
                'key' => 'verification',
                'name' => 'Verifikasi Bendahara',
                'permission' => 'peminjaman.pengajuan.approve_bendahara',
            ],
            [
                'key' => 'manager_approval',
                'name' => 'Persetujuan Manager',
                'permission' => 'peminjaman.pengajuan.approve_manager',
            ],
            [
                'key' => 'ketua_approval',
                'name' => 'Persetujuan Ketua',
                'permission' => 'peminjaman.persetujuan.approve_ketua',
            ],
            [
                'key' => 'disbursement',
                'name' => 'Pencairan',
                'permission' => 'peminjaman.persetujuan.disburse',
            ],
        ]);

        try {
            $peminjaman = Peminjaman::factory()->create();
            $service = app(PeminjamanService::class);

            $service->review($peminjaman);
            $service->completeReview($peminjaman);
            $this->assertSame('manager_approval', $service->currentWorkflowStep($peminjaman)?->step_key);

            $service->processCurrentWorkflowStep($peminjaman);

            $this->assertSame('ketua_approval', $service->currentWorkflowStep($peminjaman)?->step_key);
            $service->approve($peminjaman);

            $this->assertSame(StatusPeminjaman::Approved, $peminjaman->fresh()->status);
            $this->assertSame('disbursement', $service->currentWorkflowStep($peminjaman)?->step_key);
        } finally {
            config()->set('peminjaman.approval_workflows', $originalWorkflow);
        }
    }

    public function test_peminjaman_follows_review_approval_and_disbursement_stages(): void
    {
        $peminjaman = Peminjaman::factory()->create();
        $service = app(PeminjamanService::class);

        $service->review($peminjaman);
        $this->assertSame(StatusPeminjaman::InReview, $peminjaman->fresh()->status);

        $service->completeReview($peminjaman);
        $this->assertSame(StatusPeminjaman::ReadyForApproval, $peminjaman->fresh()->status);
    }

    public function test_complete_review_records_rekomendasi_for_ketua(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $peminjaman = Peminjaman::factory()->create();
        $service = app(PeminjamanService::class);

        $service->review($peminjaman);
        $result = $service->completeReview($peminjaman, 'Kas tersedia, dokumen lengkap, layak disetujui.');

        $this->assertSame(StatusPeminjaman::ReadyForApproval, $result->status);
        $this->assertSame('Kas tersedia, dokumen lengkap, layak disetujui.', $result->rekomendasi);
        $this->assertSame($user->getKey(), $result->rekomendasi_by);
        $this->assertNotNull($result->rekomendasi_at);
    }

    public function test_rejection_records_the_stage_and_message(): void
    {
        $peminjaman = Peminjaman::factory()->create();
        $service = app(PeminjamanService::class);

        $service->review($peminjaman);
        $service->rejectDuringReview($peminjaman, 'Dokumen belum lengkap.');

        $rejected = $peminjaman->fresh();

        $this->assertSame(StatusPeminjaman::Rejected, $rejected->status);
        $this->assertSame('PEMERIKSAAN', $rejected->rejection_stage->value);
        $this->assertSame('Dokumen belum lengkap.', $rejected->rejection_reason);
    }
}
