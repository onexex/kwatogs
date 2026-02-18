<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetEmployeeNumbers extends Command
{
    protected $signature = 'employee:reset-numbers';
    protected $description = 'Reset employee IDs with safety checks and implicit commit prevention';

    public function handle()
    {
        if (!$this->confirm('Have you backed up your database? This will permanently re-map all employee IDs.')) {
            return;
        }

        $affectedTables = [
            ['table' => 'emp_infos', 'column' => 'empID'],
            ['table' => 'emp_educations', 'column' => 'empID'],
            ['table' => 'emp_details', 'column' => 'empID'],
            ['table' => 'emp_details', 'column' => 'empISID'],
            ['table' => 'access', 'column' => 'empID'],
        ];

        // 1. Disable constraints sa simula pa lang (Outside transaction)
        Schema::disableForeignKeyConstraints();

        $newIDCounter = 1;

        try {
            // 2. Simulan ang transaction para lang sa DATA updates (DML)
            DB::beginTransaction();

            $users = DB::table('users')->orderBy('empID', 'asc')->get();
            
            foreach ($users as $user) {
                $oldID = $user->empID;
                $newID = $newIDCounter;

                if ($oldID != $newID) {
                    foreach ($affectedTables as $entry) {
                        DB::table($entry['table'])
                            ->where($entry['column'], $oldID)
                            ->update([$entry['column'] => $newID]);
                    }
                    DB::table('users')->where('empID', $oldID)->update(['empID' => $newID]);
                    $this->line("Mapping: $oldID -> $newID");
                }
                $newIDCounter++;
            }

            // 3. I-save muna ang lahat ng data updates
            DB::commit();

            // 4. RESET AUTO-INCREMENT (Dito nag-e-error dati dahil bawal ito sa loob ng transaction)
            // Ngayong commit na ang data, safe na itong i-run
            DB::statement("ALTER TABLE users AUTO_INCREMENT = $newIDCounter");
            
            $this->info("Success! IDs reset from 1 to " . ($newIDCounter - 1));

        } catch (\Exception $e) {
            // Check if there is still a transaction before rolling back
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error("Error encountered: " . $e->getMessage());
        } finally {
            // 5. Siguraduhing ma-enable ulit ang checks
            Schema::enableForeignKeyConstraints();
        }
    }
}