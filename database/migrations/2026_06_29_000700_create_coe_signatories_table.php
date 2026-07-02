<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE signatories — the authorized people whose uploaded e-signature can be
 * stamped on a Certificate of Employment. HR maintains a list (Settings →
 * COE Signatories) and picks one per COE at approval/issuance time; there is
 * no draw-it-each-time signature pad. The chosen signatory's name, title and
 * signature image are frozen onto the CoeRequest when the certificate is
 * issued, so editing/removing a signatory later never alters past certificates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coe_signatories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('signature_path');           // relative public path, e.g. img/signatories/sig_xxx.png
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_signatories');
    }
};
