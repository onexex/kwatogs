<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action');                 // created | updated | deleted
            $table->string('model');                  // class basename
            $table->string('model_id')->nullable();
            $table->json('changes')->nullable();      // {field: {from, to}} or attributes
            $table->string('ip', 64)->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['model', 'created_at']);
            $table->index('user_id');
        });

        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'auditlog', 'guard_name' => 'web']);
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
        Schema::dropIfExists('audit_logs');
        try {
            \Spatie\Permission\Models\Permission::where('name', 'auditlog')->delete();
        } catch (\Throwable $e) {
        }
    }
};
