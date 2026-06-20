<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);                 // smtp | mailgun | ses | postmark
            $table->string('label')->nullable();             // friendly name, e.g. "Brevo - Production"
            $table->string('from_address');
            $table->string('from_name');
            $table->text('config')->nullable();              // encrypted JSON blob (host/port/api keys/etc.)
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 20)->nullable(); // success | failed
            $table->text('last_test_message')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('provider');
            $table->index('is_active');
        });

        // Register the permission key so the screen is gated even before
        // `php artisan app:create-permission` is run, mirroring the audit_logs migration.
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'mailintegration', 'guard_name' => 'web']);
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
        Schema::dropIfExists('mail_integration_settings');
        try {
            \Spatie\Permission\Models\Permission::where('name', 'mailintegration')->delete();
        } catch (\Throwable $e) {
        }
    }
};
