<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('fname')->nullable()->after('email'); //just add this line
            $table->text('lname')->nullable()->after('email'); //just add this line
            $table->text('mname')->nullable()->after('email'); //just add this line
            $table->text('suffix')->nullable()->after('email'); //just add this line
            $table->char('status')->nullable()->after('email'); //just add this line
       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
