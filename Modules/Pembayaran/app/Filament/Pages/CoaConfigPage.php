<?php

namespace Modules\Pembayaran\Filament\Pages;

use App\Models\CoaAccount;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig;
use UnitEnum;

class CoaConfigPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Pembayaran';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'COA Pembayaran';

    protected static ?string $slug = 'coa-pembayaran';

    protected static ?int $navigationSort = 40;

    protected string $view = 'pembayaran::filament.pages.coa-config';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pembayaran.coa_config.viewAny') ?? false;
    }

    public function mount(): void
    {
        $savedConfigs = CoaConfig::query()->pluck('coa_account_id', 'key')->all();

        $this->form->fill([
            'configs' => collect(PembayaranCOAConfigEnum::cases())
                ->mapWithKeys(fn (PembayaranCOAConfigEnum $case): array => [
                    $case->value => $savedConfigs[$case->value] ?? null,
                ])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $coaAccountOptions = CoaAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (CoaAccount $account): array => [
                $account->getKey() => "{$account->code} - {$account->name}",
            ])
            ->all();

        return $schema
            ->statePath('data')
            ->components([
                ...collect(PembayaranCOAConfigEnum::cases())
                    ->map(fn (PembayaranCOAConfigEnum $case): Section => Section::make($case->name)
                        ->description($case->value)
                        ->schema([
                            Select::make("configs.{$case->value}")
                                ->label('COA Account')
                                ->options($coaAccountOptions)
                                ->searchable()
                                ->preload()
                                ->nullable(),
                        ]))
                    ->all(),
            ]);
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()?->can('pembayaran.coa_config.create')
                || auth()->user()?->can('pembayaran.coa_config.update'),
            403,
        );

        $configs = $this->form->getState()['configs'] ?? [];

        DB::transaction(function () use ($configs): void {
            foreach (PembayaranCOAConfigEnum::cases() as $case) {
                CoaConfig::query()->updateOrCreate(
                    ['key' => $case->value],
                    ['coa_account_id' => $configs[$case->value] ?? null],
                );
            }
        });

        Notification::make()->title('COA Pembayaran berhasil disimpan')->success()->send();
    }
}
