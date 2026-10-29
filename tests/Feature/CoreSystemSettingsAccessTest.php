<?php

namespace Tests\Feature;

use App\Filament\Resources\PemulihanBackups\PemulihanBackupResource;
use App\Filament\Resources\PesanNotifikasis\PesanNotifikasiResource;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class CoreSystemSettingsAccessTest extends TestCase
{
    public function test_only_super_admin_can_access_core_notification_and_restore_resources(): void
    {
        $user = Mockery::mock(User::class, Authenticatable::class);
        $user->shouldReceive('hasRole')->with('super_admin')->andReturn(false);
        $superAdmin = Mockery::mock(User::class, Authenticatable::class);
        $superAdmin->shouldReceive('hasRole')->with('super_admin')->andReturn(true);

        Auth::setUser($user);
        $this->assertFalse(PesanNotifikasiResource::canAccess());
        $this->assertFalse(PemulihanBackupResource::canAccess());

        Auth::setUser($superAdmin);
        $this->assertTrue(PesanNotifikasiResource::canAccess());
        $this->assertTrue(PemulihanBackupResource::canAccess());
    }
}
