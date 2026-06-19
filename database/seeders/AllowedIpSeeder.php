<?php

namespace Database\Seeders;

use App\Models\AllowedIp;
use Illuminate\Database\Seeder;

class AllowedIpSeeder extends Seeder
{
    /**
     * Seed the allowed_ips table with initial trusted IP addresses.
     *
     * Replace the sample values below with the actual office/branch IPs
     * before running in production.
     *
     * Supports both IPv4 (e.g. 192.168.1.1) and IPv6 (up to 45 chars).
     */
    public function run(): void
    {
        $ips = [
            [
                'ip_address'  => '192.168.1.1',
                'description' => 'Main Office - Head Office Gateway',
                'status'      => true,
                'created_by'  => null,
            ],
            [
                'ip_address'  => '192.168.1.2',
                'description' => 'Main Office - HR Workstation',
                'status'      => true,
                'created_by'  => null,
            ],
            [
                'ip_address'  => '127.0.0.1',
                'description' => 'Localhost (development)',
                'status'      => true,
                'created_by'  => null,
            ],
        ];

        foreach ($ips as $ip) {
            AllowedIp::updateOrCreate(
                ['ip_address' => $ip['ip_address']],
                $ip
            );
        }
    }
}
