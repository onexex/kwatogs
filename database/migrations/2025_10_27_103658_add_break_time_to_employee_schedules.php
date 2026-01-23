<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->time('break_start')->nullable()->after('sched_in');
            $table->time('break_end')->nullable()->after('break_start');
        });
    }

    public function down()
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropColumn(['break_start', 'break_end']);
        });
    }

};
