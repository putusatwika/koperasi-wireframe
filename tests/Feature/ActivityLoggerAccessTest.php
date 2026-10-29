<?php

namespace Tests\Feature;

use App\Filament\Resources\AktivitasPenggunas\AktivitasPenggunaResource;
use App\Models\AktivitasPengguna;
use App\Models\User;
use App\Support\Concerns\UseLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ActivityLoggerAccessTest extends TestCase
{
    public function test_only_super_admin_can_access_activity_logs(): void
    {
        $user = Mockery::mock(User::class, Authenticatable::class);
        $user->shouldReceive('hasRole')->with('super_admin')->andReturn(false);
        $superAdmin = Mockery::mock(User::class, Authenticatable::class);
        $superAdmin->shouldReceive('hasRole')->with('super_admin')->andReturn(true);

        Auth::setUser($user);
        $this->assertFalse(AktivitasPenggunaResource::canAccess());

        Auth::setUser($superAdmin);
        $this->assertTrue(AktivitasPenggunaResource::canAccess());
    }

    public function test_model_crud_is_written_to_activity_logs(): void
    {
        Schema::create('aktivitas_penggunas', function ($table): void {
            $table->id();
            $table->string('aksi');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('status_sebelum')->nullable();
            $table->string('status_sesudah')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('logger_test_items', function ($table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        try {
            $modelClass = new class extends Model
            {
                use UseLogger;

                protected $table = 'logger_test_items';

                protected $guarded = [];
            };

            $record = $modelClass::query()->create(['name' => 'created']);
            $record->update(['name' => 'updated']);
            $record->delete();

            $this->assertSame(3, AktivitasPengguna::query()->count());
            $this->assertSame('created', AktivitasPengguna::query()->where('aksi', 'created')->value('aksi'));
            $this->assertSame(get_class($record), AktivitasPengguna::query()->first()->metadata['model_type'] ?? null);
        } finally {
            Schema::dropIfExists('logger_test_items');
            Schema::dropIfExists('aktivitas_penggunas');
        }
    }
}
