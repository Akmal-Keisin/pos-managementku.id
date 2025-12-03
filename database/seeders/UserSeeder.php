<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role' => 'super-admin',
            'email_verified_at' => now(),
        ]);

        // Create Admins
        $admins = [
            ['name' => 'Ahmad Rifai', 'username' => 'ahmad.rifai'],
            ['name' => 'Siti Nurhaliza', 'username' => 'siti.nurhaliza'],
            ['name' => 'Budi Santoso', 'username' => 'budi.santoso'],
        ];

        foreach ($admins as $admin) {
            User::create([
                'name' => $admin['name'],
                'username' => $admin['username'],
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        // Create Cashiers
        $cashiers = [
            ['name' => 'Dewi Lestari', 'username' => 'dewi.lestari'],
            ['name' => 'Eko Prasetyo', 'username' => 'eko.prasetyo'],
            ['name' => 'Fitri Handayani', 'username' => 'fitri.handayani'],
            ['name' => 'Gunawan Wijaya', 'username' => 'gunawan.wijaya'],
            ['name' => 'Heni Kusuma', 'username' => 'heni.kusuma'],
        ];

        foreach ($cashiers as $cashier) {
            User::create([
                'name' => $cashier['name'],
                'username' => $cashier['username'],
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'email_verified_at' => now(),
            ]);
        }
    }
}
