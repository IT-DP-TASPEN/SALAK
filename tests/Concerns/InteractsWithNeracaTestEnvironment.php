<?php

namespace Tests\Concerns;

use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\PanelRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait InteractsWithNeracaTestEnvironment
{
    protected string $databasePath;

    protected function bootNeracaTestEnvironment(): void
    {
        Carbon::setTestNow('2026-01-08 12:00:00');

        $this->databasePath = tempnam(sys_get_temp_dir(), 'salak-neraca-');

        $sharedSqliteConfig = [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => $sharedSqliteConfig,
            'database.connections.mysql' => $sharedSqliteConfig,
            'cache.default' => 'array',
        ]);

        DB::purge('sqlite');
        DB::purge('mysql');
        DB::reconnect('sqlite');
        DB::reconnect('mysql');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createNeracaSchema();

        Filament::setCurrentPanel(app(PanelRegistry::class)->get('admin'));
    }

    protected function destroyNeracaTestEnvironment(): void
    {
        Carbon::setTestNow();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::disconnect('sqlite');
        DB::disconnect('mysql');

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }
    }

    protected function createBranchOffice(int $id, string $fincloudCode, string $name): void
    {
        DB::table('branch_offices')->insert([
            'id' => $id,
            'branch_code' => substr($fincloudCode, -2),
            'branch_code_fincloud' => $fincloudCode,
            'branch_name' => $name,
            'branch_saldo_aba_blokir' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createSaldoNeraca(string $date, string $branchCode, string $account, float $balance): void
    {
        DB::table('saldo_neracas')->insert([
            'cabang' => $branchCode,
            'tanggal' => $date,
            'noakun' => $account,
            'namaakun' => $account,
            'saldoawal' => 0,
            'mutasidebit' => 0,
            'mutasikredit' => 0,
            'saldoakhir' => $balance,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createUserWithBranch(string $name, int $branchOfficeId): User
    {
        return User::query()->create([
            'name' => $name,
            'username' => strtolower(str_replace(' ', '_', $name)),
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'password' => 'password',
            'branch_office_id' => $branchOfficeId,
        ]);
    }

    protected function grantNeracaPagePermission(User $user): void
    {
        $permission = Permission::findOrCreate('page_Neraca', 'web');
        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function grantLabaRugiPagePermission(User $user): void
    {
        $permission = Permission::findOrCreate('page_LabaRugi', 'web');
        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createNeracaSchema(): void
    {
        Schema::create('branch_offices', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_code')->nullable();
            $table->string('branch_code_fincloud')->unique();
            $table->string('branch_name')->nullable();
            $table->decimal('branch_saldo_aba_blokir', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('saldo_neracas', function (Blueprint $table): void {
            $table->id();
            $table->char('cabang', 3);
            $table->date('tanggal');
            $table->string('noakun');
            $table->string('namaakun')->nullable();
            $table->decimal('saldoawal', 15, 2)->default(0);
            $table->decimal('mutasidebit', 15, 2)->default(0);
            $table->decimal('mutasikredit', 15, 2)->default(0);
            $table->decimal('saldoakhir', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('branch_office_id')->nullable();
            $table->rememberToken()->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
