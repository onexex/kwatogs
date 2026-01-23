<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE loans 
            MODIFY COLUMN loan_type 
            ENUM('salary', 'sss', 'pagibig', 'philhealth', 'other', 'charges/penalty', 'cash_adv') 
            NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE loans 
            MODIFY COLUMN loan_type 
            ENUM('salary', 'sss', 'pagibig', 'philhealth') 
            NOT NULL
        ");
    }
};
