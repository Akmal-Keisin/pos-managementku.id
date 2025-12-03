<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * IMPORTANT: Seeders must be run in this specific order to maintain data integrity:
     * 1. UserSeeder - Creates users first (required by StockHistory and Transaction)
     * 2. ProductSeeder - Creates products (required by StockHistory, TransactionDetail)
     * 3. StockHistorySeeder - Creates stock movements (references User and Product)
     * 4. TransactionSeeder - Creates transactions with details (references User, Product)
     *
     * After running seeders, you will have:
     * - 1 Super Admin (superadmin/password)
     * - 3 Admins (password: password)
     * - 5 Cashiers (password: password)
     * - 30 Products across various categories with realistic stock levels
     * - Stock movements over the past 60 days
     * - Hundreds of realistic transactions over the past 60 days
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            StockHistorySeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
