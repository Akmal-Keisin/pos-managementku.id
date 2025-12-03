<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admins (only admins can manage stock)
        $admins = User::whereIn('role', ['super-admin', 'admin'])->get();

        // Get all products
        $products = Product::all();

        // Create initial stock increase records for all products (simulating initial inventory)
        // This happened 60 days ago
        foreach ($products as $product) {
            $admin = $admins->random();

            StockHistory::create([
                'product_id' => $product->id,
                'user_id' => $admin->id,
                'type' => 'increase',
                'quantity' => $product->current_stock,
                'notes' => 'Initial stock for ' . $product->name,
                'created_at' => now()->subDays(60),
                'updated_at' => now()->subDays(60),
            ]);
        }

        // Create additional stock movements over the past 60 days
        $stockMovements = [
            // Week 8 (56 days ago) - Restocking popular items
            [
                'product_sku' => 'BEV-TB-001',
                'type' => 'increase',
                'quantity' => 100,
                'notes' => 'Restocking Teh Botol - high demand',
                'days_ago' => 56,
            ],
            [
                'product_sku' => 'SNK-IG-001',
                'type' => 'increase',
                'quantity' => 150,
                'notes' => 'Restocking Indomie Goreng',
                'days_ago' => 56,
            ],
            [
                'product_sku' => 'BEV-AQ-002',
                'type' => 'increase',
                'quantity' => 120,
                'notes' => 'Restocking Aqua',
                'days_ago' => 55,
            ],

            // Week 7 (49 days ago) - Stock adjustments
            [
                'product_sku' => 'CIG-SM-001',
                'type' => 'increase',
                'quantity' => 80,
                'notes' => 'Restocking Sampoerna Mild',
                'days_ago' => 49,
            ],
            [
                'product_sku' => 'ST-BP-001',
                'type' => 'increase',
                'quantity' => 30,
                'notes' => 'Restocking Beras Pandan Wangi',
                'days_ago' => 48,
            ],

            // Week 6 (42 days ago) - Damage/expiry adjustments
            [
                'product_sku' => 'DE-TA-001',
                'type' => 'decrease',
                'quantity' => 5,
                'notes' => 'Damaged eggs - not sellable',
                'days_ago' => 42,
            ],
            [
                'product_sku' => 'SNK-BB-005',
                'type' => 'decrease',
                'quantity' => 3,
                'notes' => 'Expired products removal',
                'days_ago' => 41,
            ],

            // Week 5 (35 days ago) - Regular restocking
            [
                'product_sku' => 'SNK-CH-002',
                'type' => 'increase',
                'quantity' => 40,
                'notes' => 'Restocking Chitato',
                'days_ago' => 35,
            ],
            [
                'product_sku' => 'PC-PP-001',
                'type' => 'increase',
                'quantity' => 30,
                'notes' => 'Restocking Pepsodent',
                'days_ago' => 34,
            ],
            [
                'product_sku' => 'HH-RD-001',
                'type' => 'increase',
                'quantity' => 25,
                'notes' => 'Restocking Rinso',
                'days_ago' => 33,
            ],

            // Week 4 (28 days ago) - High demand restocking
            [
                'product_sku' => 'BEV-UM-004',
                'type' => 'increase',
                'quantity' => 60,
                'notes' => 'Restocking Ultra Milk',
                'days_ago' => 28,
            ],
            [
                'product_sku' => 'BEV-PS-005',
                'type' => 'increase',
                'quantity' => 50,
                'notes' => 'Restocking Pocari Sweat - summer demand',
                'days_ago' => 27,
            ],

            // Week 3 (21 days ago) - Frozen food restocking
            [
                'product_sku' => 'FF-NF-001',
                'type' => 'increase',
                'quantity' => 20,
                'notes' => 'Restocking Nugget Fiesta',
                'days_ago' => 21,
            ],
            [
                'product_sku' => 'FF-SN-002',
                'type' => 'increase',
                'quantity' => 15,
                'notes' => 'Restocking Sosis So Nice',
                'days_ago' => 20,
            ],

            // Week 2 (14 days ago) - Regular weekly restocking
            [
                'product_sku' => 'BEV-KK-003',
                'type' => 'increase',
                'quantity' => 80,
                'notes' => 'Weekly restocking Kopi Kapal Api',
                'days_ago' => 14,
            ],
            [
                'product_sku' => 'SNK-OR-003',
                'type' => 'increase',
                'quantity' => 35,
                'notes' => 'Restocking Oreo',
                'days_ago' => 13,
            ],
            [
                'product_sku' => 'ST-MG-002',
                'type' => 'increase',
                'quantity' => 25,
                'notes' => 'Restocking Minyak Goreng',
                'days_ago' => 12,
            ],

            // Week 1 (7 days ago) - Recent restocking
            [
                'product_sku' => 'CIG-GG-002',
                'type' => 'increase',
                'quantity' => 70,
                'notes' => 'Restocking Gudang Garam Filter',
                'days_ago' => 7,
            ],
            [
                'product_sku' => 'SNK-WT-004',
                'type' => 'increase',
                'quantity' => 100,
                'notes' => 'Restocking Wafer Tango',
                'days_ago' => 6,
            ],
            [
                'product_sku' => 'CD-KB-001',
                'type' => 'increase',
                'quantity' => 40,
                'notes' => 'Restocking Kecap Bango',
                'days_ago' => 5,
            ],

            // Recent days - Minor adjustments
            [
                'product_sku' => 'PC-LB-002',
                'type' => 'increase',
                'quantity' => 50,
                'notes' => 'Restocking Lifebuoy',
                'days_ago' => 3,
            ],
            [
                'product_sku' => 'DE-KK-002',
                'type' => 'decrease',
                'quantity' => 2,
                'notes' => 'Quality control - damaged packaging',
                'days_ago' => 2,
            ],
        ];

        foreach ($stockMovements as $movement) {
            $product = Product::where('sku', $movement['product_sku'])->first();

            if ($product) {
                $admin = $admins->random();

                StockHistory::create([
                    'product_id' => $product->id,
                    'user_id' => $admin->id,
                    'type' => $movement['type'],
                    'quantity' => $movement['quantity'],
                    'notes' => $movement['notes'],
                    'created_at' => now()->subDays($movement['days_ago']),
                    'updated_at' => now()->subDays($movement['days_ago']),
                ]);

                // Update product stock accordingly (but don't save it yet, TransactionSeeder will handle final stock)
                // This is just for realistic stock history tracking
            }
        }
    }
}
