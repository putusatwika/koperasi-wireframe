<?php

namespace Modules\ExampleModule\Filament\Pages;

use App\Models\CoaAccount;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\ExampleModule\Enums\ExampleModuleCOAConfigEnum;
use Modules\ExampleModule\Models\CoaConfig;
use UnitEnum;

class CoaConfigPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Example Module';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'COA Config';

    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    protected string $view = 'examplemodule::filament.pages.coa-config';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('examplemodule.coa_config.viewAny') ?? false;
    }

    public function mount(): void
    {
        $savedConfigs = CoaConfig::query()
            ->pluck('coa_account_id', 'key')
            ->all();

        $this->form->fill([
            'configs' => collect(ExampleModuleCOAConfigEnum::cases())
                ->mapWithKeys(fn (ExampleModuleCOAConfigEnum $case): array => [
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
                ...collect(ExampleModuleCOAConfigEnum::cases())
                    ->map(fn (ExampleModuleCOAConfigEnum $case): Section => Section::make($case->name)
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
            auth()->user()?->can('examplemodule.coa_config.create')
                || auth()->user()?->can('examplemodule.coa_config.update'),
            403,
        );

        $configs = $this->form->getState()['configs'] ?? [];

        DB::transaction(function () use ($configs): void {
            foreach (ExampleModuleCOAConfigEnum::cases() as $case) {
                CoaConfig::query()->updateOrCreate(
                    ['key' => $case->value],
                    ['coa_account_id' => $configs[$case->value] ?? null],
                );
            }
        });

        Notification::make()
            ->title('COA Config berhasil disimpan')
            ->success()
            ->send();
    }
}
