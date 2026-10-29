<?php

namespace Modules\Keuangan\Filament\Pages;

use App\Models\CoaAccount;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;
use Modules\Keuangan\Models\CoaConfig;
use UnitEnum;

class CoaConfigPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'COA Keuangan';

    protected static ?string $slug = 'coa-keuangan';

    protected static ?int $navigationSort = 40;

    protected string $view = 'keuangan::filament.pages.coa-config';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('keuangan.coa_config.viewAny') ?? false;
    }

    public function mount(): void
    {
        $savedConfigs = CoaConfig::query()->pluck('coa_account_id', 'key')->all();

        $this->form->fill([
            'configs' => collect(KeuanganCOAConfigEnum::cases())
                ->mapWithKeys(fn (KeuanganCOAConfigEnum $case): array => [
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
                ...collect(KeuanganCOAConfigEnum::cases())
                    ->map(fn (KeuanganCOAConfigEnum $case): Section => Section::make($case->name)
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
            auth()->user()?->can('keuangan.coa_config.create')
                || auth()->user()?->can('keuangan.coa_config.update'),
            403,
        );

        $configs = $this->form->getState()['configs'] ?? [];

        DB::transaction(function () use ($configs): void {
            foreach (KeuanganCOAConfigEnum::cases() as $case) {
                CoaConfig::query()->updateOrCreate(
                    ['key' => $case->value],
                    ['coa_account_id' => $configs[$case->value] ?? null],
                );
            }
        });

        Notification::make()->title('COA Keuangan berhasil disimpan')->success()->send();
    }
}
