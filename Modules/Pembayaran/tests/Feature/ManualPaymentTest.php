<?php

namespace Modules\Pembayaran\Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Pembayaran\Enums\JenisPembayaran;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig as PembayaranCoaConfig;
use Modules\Pembayaran\Services\PembayaranService;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Models\JadwalAngsuran;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('peminjaman', require base_path('Modules/Peminjaman/config/config.php'));
        config()->set('pembayaran', require base_path('Modules/Pembayaran/config/config.php'));
        config()->set('keanggotaan', require base_path('Modules/Keanggotaan/config/config.php'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->runMigrations();
    }

    public function test_bendahara_can_record_manual_payment_for_a_member_loan(): void
    {
        $user = $this->actingUser(['pembayaran.pembayaran.create']);
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));
        $this->configurePembayaranCoa();

        $payment = app(PembayaranService::class)->createManualPayment([
            'peminjaman_id' => $peminjaman->getKey(),
            'payment_date' => '2026-11-15',
            'amount' => 112000,
            'principal_amount' => 100000,
            'interest_amount' => 12000,
        ]);

        $this->assertSame(JenisPembayaran::Manual, $payment->payment_type);
        $this->assertSame($user->getKey(), $payment->created_by);

        $first = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->firstOrFail();

        $this->assertSame(StatusJadwalAngsuran::Lunas, $first->status);
    }

    public function test_manual_payment_requires_permission(): void
    {
        $user = $this->actingUser();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));
        $this->configurePembayaranCoa();

        $this->expectException(HttpException::class);

        app(PembayaranService::class)->createManualPayment([
            'peminjaman_id' => $peminjaman->getKey(),
            'payment_date' => '2026-11-15',
            'amount' => 112000,
            'principal_amount' => 100000,
            'interest_amount' => 12000,
        ]);
    }

    public function test_settle_early_pays_remaining_principal_interest_and_penalty(): void
    {
        $user = $this->actingUser(['pembayaran.pembayaran.create']);
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));
        $this->configurePembayaranCoa();

        $installments = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->get();

        $installments[0]->update([
            'dibayar_pokok' => 100000,
            'dibayar_bunga' => 12000,
            'status' => StatusJadwalAngsuran::Lunas,
        ]);
        $installments[1]->update([
            'dibayar_pokok' => 50000,
            'dibayar_bunga' => 0,
            'status' => StatusJadwalAngsuran::Sebagian,
        ]);

        $payment = app(PembayaranService::class)->settleEarly($peminjaman->refresh(), [
            'payment_date' => '2026-12-01',
        ]);

        $this->assertEquals(1050000.0, (float) $payment->principal_amount);
        $this->assertEquals(132000.0, (float) $payment->interest_amount);
        $this->assertEquals(21000.0, (float) $payment->penalty_amount);
        $this->assertEquals(1203000.0, (float) $payment->amount);

        $this->assertSame(
            12,
            JadwalAngsuran::query()
                ->where('peminjaman_id', $peminjaman->getKey())
                ->where('status', StatusJadwalAngsuran::Lunas->value)
                ->count(),
        );

        $peminjaman->refresh();
        $this->assertSame(StatusPeminjaman::Lunas, $peminjaman->status);
        $this->assertNotNull($peminjaman->lunas_at);
        $this->assertSame($user->getKey(), $peminjaman->lunas_by);

        $debit = (float) Jurnal::query()
            ->where('source_module', 'Pembayaran')
            ->where('source_id', $payment->getKey())
            ->where('position', 'DEBIT')
            ->sum('amount');
        $credit = (float) Jurnal::query()
            ->where('source_module', 'Pembayaran')
            ->where('source_id', $payment->getKey())
            ->where('position', 'CREDIT')
            ->sum('amount');

        $this->assertEquals(1203000.0, $debit);
        $this->assertEquals($debit, $credit);
    }

    public function test_settle_early_requires_permission(): void
    {
        $user = $this->actingUser();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $this->expectException(HttpException::class);

        app(PembayaranService::class)->settleEarly($peminjaman);
    }

    public function test_first_installment_deduction_is_recorded_without_journals(): void
    {
        $user = User::factory()->create();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));

        $record = app(PembayaranService::class)->recordFirstInstallmentDeduction(
            $peminjaman,
            100000,
            12000,
            '2026-10-15',
            $user->getKey(),
        );

        $this->assertSame(JenisPembayaran::Potongan, $record->payment_type);
        $this->assertSame(1, $record->installment_number);
        $this->assertSame('112000.00', $record->amount);
        $this->assertSame($user->getKey(), $record->created_by);

        $this->assertSame(
            0,
            Jurnal::query()->where('source_module', 'Pembayaran')->where('source_id', $record->getKey())->count(),
        );

        $first = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->firstOrFail();

        $this->assertSame(StatusJadwalAngsuran::Belum, $first->status);
    }

    public function test_penalty_payment_is_recorded_as_denda_dibayar(): void
    {
        $this->actingUser(['pembayaran.pembayaran.create']);
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), User::factory()->create());
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));
        $this->configurePembayaranCoa();

        $service = app(PeminjamanService::class);
        $first = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->orderBy('angsuran_ke')
            ->firstOrFail();
        $asOf = CarbonImmutable::parse('2026-10-25');

        $this->assertSame(1120.0, $service->dendaBelumDibayar($first, $asOf));

        app(PembayaranService::class)->createManualPayment([
            'peminjaman_id' => $peminjaman->getKey(),
            'payment_date' => '2026-10-25',
            'amount' => 1120,
            'penalty_amount' => 1120,
        ]);

        $this->assertSame('1120.00', $first->refresh()->denda_dibayar);
        $this->assertSame(0.0, $service->dendaBelumDibayar($first->refresh(), $asOf));
        $this->assertSame(560.0, $service->dendaBelumDibayar($first->refresh(), CarbonImmutable::parse('2026-10-30')));
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
            'Modules/Peminjaman/database/migrations/2026_10_20_000001_add_personal_application_details_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_12_000001_create_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000001_create_peminjaman_jadwal_angsuran_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000002_add_fee_and_penalty_to_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000003_add_fee_and_penalty_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000004_add_lunas_tracking_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000005_add_denda_dibayar_to_jadwal_angsuran_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100000_create_pembayarans_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100010_create_coa_config_table.php',
        ];

        foreach ($migrations as $migration) {
            (require base_path($migration))->up();
        }
    }
}
