<?php

namespace Modules\Peminjaman\Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig as PembayaranCoaConfig;
use Modules\Pembayaran\Services\PembayaranService;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\PeminjamanCOAConfigEnum;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Events\PinjamanDicairkan;
use Modules\Peminjaman\Http\Controllers\KartuPiutangCetakController;
use Modules\Peminjaman\Models\CoaConfig as PeminjamanCoaConfig;
use Modules\Peminjaman\Models\JadwalAngsuran;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class JadwalAngsuranTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('peminjaman', require base_path('Modules/Peminjaman/config/config.php'));
        config()->set('pembayaran', require base_path('Modules/Pembayaran/config/config.php'));
        config()->set('keanggotaan', require base_path('Modules/Keanggotaan/config/config.php'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Namespace view modul hanya terdaftar saat modul aktif; daftarkan
        // manual agar controller cetak dapat diuji.
        view()->addNamespace('peminjaman', base_path('Modules/Peminjaman/resources/views'));

        $this->runMigrations();
    }

    public function test_schedule_is_generated_from_the_loan_terms(): void
    {
        $user = User::factory()->create();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);

        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $installments = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->get();

        $this->assertCount(12, $installments);
        $this->assertSame('2026-10-15', $installments[0]->jatuh_tempo->toDateString());
        $this->assertSame('2026-11-15', $installments[1]->jatuh_tempo->toDateString());
        $this->assertSame('100000.00', $installments[0]->pokok);
        $this->assertSame('12000.00', $installments[0]->bunga);
        $this->assertSame(StatusJadwalAngsuran::Belum, $installments[0]->status);
        $this->assertEquals(1200000.0, (float) $installments->sum('pokok'));
    }

    public function test_disbursement_deducts_fees_and_first_installment_and_balances_journals(): void
    {
        Event::fake([PinjamanDicairkan::class]);

        $user = User::factory()->create();
        $peminjaman = $this->approvedLoanWithWorkflow(Anggota::factory()->create(), $user);
        $this->configurePeminjamanCoa();
        $this->actingUser();

        $result = app(PeminjamanService::class)->disburse($peminjaman);

        $this->assertSame(StatusPeminjaman::Disbursed, $result->status);

        $first = JadwalAngsuran::query()
            ->where('peminjaman_id', $result->getKey())
            ->orderBy('angsuran_ke')
            ->firstOrFail();

        $this->assertSame(StatusJadwalAngsuran::Lunas, $first->status);
        $this->assertSame('100000.00', $first->dibayar_pokok);
        $this->assertSame('12000.00', $first->dibayar_bunga);

        $debit = (float) Jurnal::query()
            ->where('source_module', 'Peminjaman')
            ->where('source_id', $result->getKey())
            ->where('position', 'DEBIT')
            ->sum('amount');
        $credit = (float) Jurnal::query()
            ->where('source_module', 'Peminjaman')
            ->where('source_id', $result->getKey())
            ->where('position', 'CREDIT')
            ->sum('amount');

        $this->assertEquals(1200000.0, $debit);
        $this->assertEquals($debit, $credit);

        $cash = (float) Jurnal::query()
            ->where('source_module', 'Peminjaman')
            ->where('source_id', $result->getKey())
            ->where('jurnal_number', "PEMINJAMAN-{$result->getKey()}-CASH")
            ->value('amount');

        $this->assertEquals(1026000.0, $cash);

        Event::assertDispatched(PinjamanDicairkan::class);
    }

    public function test_payment_is_applied_to_the_earliest_installment(): void
    {
        $user = User::factory()->create();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $this->configurePembayaranCoa();
        $this->actingUser(['pembayaran.autodebet.create']);

        app(PembayaranService::class)->createAutodebetPayment([
            'peminjaman_id' => $peminjaman->getKey(),
            'payment_date' => '2026-11-15',
            'amount' => 112000,
            'principal_amount' => 100000,
            'interest_amount' => 12000,
        ]);

        $installments = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->get();

        $first = $installments->first();
        $second = $installments->get(1);

        $this->assertSame(StatusJadwalAngsuran::Lunas, $first->status);
        $this->assertSame('100000.00', $first->dibayar_pokok);
        $this->assertSame('12000.00', $first->dibayar_bunga);
        $this->assertSame('2026-11-15', $first->dibayar_pada->toDateString());
        $this->assertSame(StatusJadwalAngsuran::Belum, $second->status);
    }

    public function test_late_penalty_is_calculated_from_overdue_days(): void
    {
        $user = User::factory()->create();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $first = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->firstOrFail();

        $denda = app(PeminjamanService::class)->hitungDenda($first, CarbonImmutable::parse('2026-10-25'));

        $this->assertSame(1120.0, $denda);

        $first->update([
            'dibayar_pokok' => 100000,
            'dibayar_bunga' => 12000,
            'status' => StatusJadwalAngsuran::Lunas,
        ]);

        $this->assertSame(0.0, app(PeminjamanService::class)->hitungDenda($first->refresh(), CarbonImmutable::parse('2026-10-25')));
    }

    public function test_early_payoff_penalty_uses_remaining_principal(): void
    {
        $user = User::factory()->create();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $first = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->firstOrFail();

        $first->update([
            'dibayar_pokok' => 100000,
            'dibayar_bunga' => 12000,
            'status' => StatusJadwalAngsuran::Lunas,
        ]);

        $penalti = app(PeminjamanService::class)->hitungPenaltiPelunasan($peminjaman->refresh());

        $this->assertSame(22000.0, $penalti);
    }

    public function test_bendahara_can_mark_a_disbursed_loan_as_lunas(): void
    {
        $user = $this->actingUser(['peminjaman.daftar_peminjaman.lunas']);
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $result = app(PeminjamanService::class)->tandaiLunas($peminjaman);

        $this->assertSame(StatusPeminjaman::Lunas, $result->status);
        $this->assertNotNull($result->lunas_at);
        $this->assertSame($user->getKey(), $result->lunas_by);
    }

    public function test_marking_lunas_requires_permission(): void
    {
        $user = $this->actingUser();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $this->expectException(HttpException::class);

        app(PeminjamanService::class)->tandaiLunas($peminjaman);
    }

    public function test_marking_lunas_twice_fails(): void
    {
        $user = $this->actingUser(['peminjaman.daftar_peminjaman.lunas']);
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        app(PeminjamanService::class)->tandaiLunas($peminjaman);

        $this->expectException(InvalidArgumentException::class);

        app(PeminjamanService::class)->tandaiLunas($peminjaman->refresh());
    }

    public function test_kartu_piutang_cetak_is_available_for_the_owner(): void
    {
        $owner = $this->actingUser();
        $anggota = Anggota::factory()->create(['user_id' => $owner->getKey()]);
        $peminjaman = $this->disbursedLoan($anggota, $owner);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $request = Request::create('/cetak');
        $request->setUserResolver(fn () => $owner);

        $view = app(KartuPiutangCetakController::class)($request, $peminjaman);
        $data = $view->getData();

        $this->assertCount(12, $data['rows']);
        $this->assertEquals(1200000.0, $data['sisaPokok']);
        $this->assertSame($owner->name, $data['dicetakOleh']);
    }

    public function test_kartu_piutang_cetak_requires_ownership_or_permission(): void
    {
        $owner = User::factory()->create();
        $anggota = Anggota::factory()->create(['user_id' => $owner->getKey()]);
        $peminjaman = $this->disbursedLoan($anggota, $owner);

        $request = Request::create('/cetak');
        $request->setUserResolver(fn () => $this->actingUser());

        $this->expectException(HttpException::class);

        app(KartuPiutangCetakController::class)($request, $peminjaman);
    }

    public function test_kartu_piutang_cetak_allows_bendahara_with_view_permission(): void
    {
        $bendahara = $this->actingUser(['peminjaman.daftar_peminjaman.view']);
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), User::factory()->create());
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $request = Request::create('/cetak');
        $request->setUserResolver(fn () => $bendahara);

        $view = app(KartuPiutangCetakController::class)($request, $peminjaman);

        $this->assertSame($peminjaman->getKey(), $view->getData()['peminjaman']->getKey());
    }

    private function loanData(Anggota $anggota, User $user, array $overrides = []): array
    {
        return [
            'loan_number' => 'PJM-TEST-'.strtoupper(uniqid()),
            'anggota_id' => $anggota->getKey(),
            'account_number' => $anggota->account_number,
            'simulation_type' => 'default',
            'interest_type' => JenisBungaPeminjaman::Flat,
            'interest_rate' => 12,
            'term_months' => 12,
            'loan_amount' => 1200000,
            'purpose' => TujuanPeminjaman::Productive,
            'guarantee_required' => false,
            'penalty_rate' => 0,
            'late_fee' => 0,
            'biaya_administrasi' => 50000,
            'biaya_provisi_persen' => 1,
            'denda_keterlambatan_persen' => 0.1,
            'penalti_pelunasan_persen' => 2,
            ...$overrides,
        ];
    }

    private function disbursedLoan(Anggota $anggota, User $user): Peminjaman
    {
        return Peminjaman::query()->create($this->loanData($anggota, $user, [
            'status' => StatusPeminjaman::Disbursed,
            'disbursed_at' => '2026-10-15',
            'disbursed_by' => $user->getKey(),
        ]));
    }

    private function approvedLoanWithWorkflow(Anggota $anggota, User $user): Peminjaman
    {
        $peminjaman = Peminjaman::query()->create($this->loanData($anggota, $user, [
            'status' => StatusPeminjaman::Approved,
            'approved_at' => '2026-10-14',
            'approved_by' => $user->getKey(),
        ]));

        $workflow = $peminjaman->approvalWorkflows()->create([
            'type' => 'loan',
            'status' => 'IN_PROGRESS',
            'current_step' => 3,
            'requested_by' => $user->getKey(),
        ]);

        foreach ([
            [1, 'verification'],
            [2, 'ketua_approval'],
            [3, 'disbursement'],
        ] as [$order, $key]) {
            $workflow->steps()->create([
                'step_order' => $order,
                'step_key' => $key,
                'step_name' => $key,
                'permission' => 'peminjaman.persetujuan.disburse',
                'status' => $order < 3 ? 'APPROVED' : 'PENDING',
            ]);
        }

        return $peminjaman->refresh();
    }

    private function actingUser(array $permissions = []): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $this->actingAs($user);

        return $user;
    }

    private function configurePeminjamanCoa(): void
    {
        $asset = AccountType::query()->create(['code' => 'ASSET', 'name' => 'Asset']);
        $revenue = AccountType::query()->create(['code' => 'REVENUE', 'name' => 'Pendapatan']);

        $accounts = [
            [PeminjamanCOAConfigEnum::LoanReceivable, '1200', 'PIUTANG', 'Piutang Pinjaman', $asset->getKey(), 'DEBIT'],
            [PeminjamanCOAConfigEnum::DisbursementCash, '1100', 'KAS', 'Kas', $asset->getKey(), 'DEBIT'],
            [PeminjamanCOAConfigEnum::AdminIncome, '4110', 'PENDAPATAN_ADMIN', 'Pendapatan Administrasi', $revenue->getKey(), 'CREDIT'],
            [PeminjamanCOAConfigEnum::ProvisionIncome, '4120', 'PENDAPATAN_PROVISI', 'Pendapatan Provisi', $revenue->getKey(), 'CREDIT'],
            [PeminjamanCOAConfigEnum::InterestIncome, '4130', 'PENDAPATAN_BUNGA', 'Pendapatan Bunga', $revenue->getKey(), 'CREDIT'],
        ];

        foreach ($accounts as [$key, $code, $keyMap, $name, $typeId, $normalBalance]) {
            $account = CoaAccount::query()->create([
                'code' => $code,
                'key_map' => $keyMap,
                'name' => $name,
                'account_type_id' => $typeId,
                'normal_balance' => $normalBalance,
            ]);

            PeminjamanCoaConfig::query()->create([
                'key' => $key->value,
                'coa_account_id' => $account->getKey(),
            ]);
        }
    }

    private function configurePembayaranCoa(): void
    {
        $asset = AccountType::query()->create(['code' => 'ASSET', 'name' => 'Asset']);
        $revenue = AccountType::query()->create(['code' => 'REVENUE', 'name' => 'Pendapatan']);

        $cash = CoaAccount::query()->create([
            'code' => '1100', 'key_map' => 'KAS', 'name' => 'Kas',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $receivable = CoaAccount::query()->create([
            'code' => '1200', 'key_map' => 'PIUTANG', 'name' => 'Piutang Pinjaman',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $interest = CoaAccount::query()->create([
            'code' => '4100', 'key_map' => 'PENDAPATAN_BUNGA', 'name' => 'Pendapatan Bunga',
            'account_type_id' => $revenue->getKey(), 'normal_balance' => 'CREDIT',
        ]);
        $penalty = CoaAccount::query()->create([
            'code' => '4200', 'key_map' => 'PENDAPATAN_DENDA', 'name' => 'Pendapatan Denda',
            'account_type_id' => $revenue->getKey(), 'normal_balance' => 'CREDIT',
        ]);

        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::PaymentCash->value, 'coa_account_id' => $cash->getKey()]);
        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::LoanReceivable->value, 'coa_account_id' => $receivable->getKey()]);
        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::InterestIncome->value, 'coa_account_id' => $interest->getKey()]);
        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::PenaltyIncome->value, 'coa_account_id' => $penalty->getKey()]);
    }

    private function runMigrations(): void
    {
        Schema::hasTable('users');

        $migrations = [
            'Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_10_060258_create_peminjamen_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_10_060340_create_coa_config_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_20_000001_add_personal_application_details_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_11_000002_create_peminjaman_approval_workflows_tables.php',
            'Modules/Peminjaman/database/migrations/2026_10_12_000001_create_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_12_000002_create_simulasi_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000001_create_peminjaman_jadwal_angsuran_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000002_add_fee_and_penalty_to_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000003_add_fee_and_penalty_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000004_add_lunas_tracking_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000005_add_denda_dibayar_to_jadwal_angsuran_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_28_000004_add_rekomendasi_to_peminjaman_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100000_create_pembayarans_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100010_create_coa_config_table.php',
        ];

        foreach ($migrations as $migration) {
            (require base_path($migration))->up();
        }
    }
}
