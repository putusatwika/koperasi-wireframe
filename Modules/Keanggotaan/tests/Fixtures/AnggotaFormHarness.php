<?php

namespace Modules\Keanggotaan\Tests\Fixtures;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;
use Livewire\Component;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Schemas\AnggotaForm;
use Modules\Keanggotaan\Models\Anggota;

/**
 * Minimal Livewire host for the Anggota resource form so the edit
 * validation rules can be exercised without booting the module panel.
 */
class AnggotaFormHarness extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?int $recordId = null;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        $record = Anggota::query()->with('user')->findOrFail($recordId);

        $this->form->fill([
            ...$record->attributesToArray(),
            'user' => $record->user->only(['name', 'email']),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return AnggotaForm::configure($schema)
            ->operation('edit')
            ->record(fn (): ?Anggota => $this->recordId
                ? Anggota::query()->with('user')->find($this->recordId)
                : null)
            ->statePath('data');
    }

    public function validateForm(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return '<div></div>';
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return 'form';
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }
}
