<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;

class CreatePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $enumPath = app_path('Enums/Permissions');

        foreach (File::files($enumPath) as $file) {
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            $fullClass = "App\\Enums\\Permissions\\{$className}";

            if (!class_exists($fullClass) || !method_exists($fullClass, 'toArray')) {
                continue;
            }

            $permissions = $fullClass::toArray();
            foreach ($permissions as $key => $value) {
                Permission::firstOrCreate(['name' => $key]);
            }
        }

        $users = User::where('role', 1)
            ->get();

        foreach ($users as $user) {
            $user->givePermissionTo('userroles');
            $user->givePermissionTo('accessrights');
        }
    }
}
