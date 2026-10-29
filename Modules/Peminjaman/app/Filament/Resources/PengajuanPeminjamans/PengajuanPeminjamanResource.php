<?php

namespace Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas\PeminjamanForm;
use Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas\PeminjamanInfolist;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\Pages\EditPengajuanPeminjaman;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\Pages\ListPengajuanPeminjamans;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\Pages\ViewPengajuanPeminjaman;
use Modules\Peminjaman\Filament\Resources\Tables\PengajuanPeminjamanTable;
use Modules\Peminjaman\Models\Peminjaman;
use UnitEnum;

class PengajuanPeminjamanResource extends Resource
{
    protected static ?string $model = Peminjaman::class;

    protected static ?string $slug = 'pengajuan-peminjaman';

    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static ?string $navigationLabel = 'Pengajuan Peminjaman';

    protected static ?string $modelLabel = 'Pengajuan Peminjaman';

    protected static ?string $pluralModelLabel = 'Pengajuan Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return PeminjamanForm::configure(
            $schema,
            auth()->user()?->can('peminjaman.pengajuan.create_any') ?? false,
        );
    }

    public static function infolist(Schema $schema): Schema
    {
        return PeminjamanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengajuanPeminjamanTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $canSeeAllApplications = ($user?->can('peminjaman.pengajuan.viewAny') ?? false)
            || ($user?->can('peminjaman.pengajuan.create_any') ?? false);

        return parent::getEloquentQuery()
            ->when(
                ! $canSeeAllApplications,
                fn (Builder $query): Builder => $query->whereHas('anggota', fn (Builder $anggotaQuery): Builder => $anggotaQuery->where('user_id', $user?->getKey() ?? 0)),
            )
            ->with(['anggota.user'])
            ->latest();
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('peminjaman.pengajuan.viewAny') ?? false)
            || (auth()->user()?->can('peminjaman.pengajuan.view') ?? false)
            || (auth()->user()?->can('peminjaman.pengajuan.create_any') ?? false);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('peminjaman.pengajuan.view') ?? false)
            && (($user?->can('peminjaman.pengajuan.viewAny') ?? false)
                || ($user?->can('peminjaman.pengajuan.create_any') ?? false)
                || $record->anggota?->user_id === $user?->getKey());
    }

    public static function canCreate(): bool
    {
        return (auth()->user()?->can('peminjaman.pengajuan.create') ?? false)
            || (auth()->user()?->can('peminjaman.pengajuan.create_any') ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return (auth()->user()?->can('peminjaman.pengajuan.update') ?? false)
            && in_array($record->status, [StatusPeminjaman::Draft, StatusPeminjaman::Submitted, StatusPeminjaman::Rejected], true);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.pengajuan.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('peminjaman.pengajuan.delete') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canDelete($record);
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canDeleteAny();
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengajuanPeminjamans::route('/'),
            'view' => ViewPengajuanPeminjaman::route('/{record}'),
            'edit' => EditPengajuanPeminjaman::route('/{record}/edit'),
        ];
    }
}
