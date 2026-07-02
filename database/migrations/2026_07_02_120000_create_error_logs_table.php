<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->nullable();        // error | warning (room to grow)
            $table->string('type');                          // exception class basename
            $table->text('message');
            $table->string('exception_class')->nullable();   // full FQCN
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->string('code', 32)->nullable();          // exception code / http status
            $table->longText('trace')->nullable();           // full stack trace string
            $table->string('url')->nullable();               // full request URL
            $table->string('method', 10)->nullable();        // GET | POST | ...
            $table->json('input')->nullable();               // sanitized request input
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('resolved')->default(false);     // dev/HR can dismiss a handled error
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index('resolved');
            $table->index('user_id');
        });

        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'errorlogs', 'guard_name' => 'web']);
            \Spatie\Permission\Models\Role::where(function ($q) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'accessrights'))
                  ->orWhere('name', 'like', '%admin%')->orWhere('name', 'like', '%super%');
            })->get()->each(fn ($r) => $r->givePermissionTo($perm));
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
        try {
            \Spatie\Permission\Models\Permission::where('name', 'errorlogs')->delete();
        } catch (\Throwable $e) {
        }
    }
};
