<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates realistic transactions following the business rules:
     * 1. Only users with cashier, admin, or super-admin roles can create transactions
     * 2. Each transaction decreases product stock and increases total_sold
     * 3. Transaction total = sum of all transaction_details totals
     * 4. TransactionDetail total = price * quantity
     * 5. Stock must be sufficient for each transaction
     */
    public function run(): void
    {
        // Get users who can make transactions (cashiers and admins)
        $cashiers = User::where('role', 'cashier')->get();
        $admins = User::whereIn('role', ['admin', 'super-admin'])->get();
        $allSalesUsers = $cashiers->concat($admins);

        // Get all products
        $products = Product::all();

        // Create transactions over the past 60 days
        // More recent days should have more transactions to simulate business growth
        $transactionScenarios = [
            // Small transactions (1-3 items)
            'small' => [
                ['BEV-TB-001', 'SNK-IG-001'],
                ['BEV-AQ-002', 'SNK-BB-005'],
                ['BEV-KK-003'],
                ['SNK-WT-004', 'BEV-TB-001'],
                ['CIG-SM-001'],
                ['BEV-UM-004', 'SNK-OR-003'],
                ['BEV-PS-005'],
                ['SNK-CH-002', 'BEV-AQ-002'],
                ['CIG-GG-002', 'BEV-TB-001'],
                ['SNK-IG-001', 'BEV-KK-003', 'SNK-WT-004'],
            ],

            // Medium transactions (4-7 items)
            'medium' => [
                ['BEV-TB-001', 'SNK-IG-001', 'PC-PP-001', 'PC-LB-002'],
                ['BEV-AQ-002', 'SNK-OR-003', 'BEV-UM-004', 'SNK-BB-005', 'BEV-KK-003'],
                ['CIG-SM-001', 'BEV-TB-001', 'SNK-WT-004', 'SNK-IG-001'],
                ['PC-SS-003', 'PC-TP-004', 'PC-PP-001', 'PC-LB-002'],
                ['ST-GP-003', 'ST-TT-004', 'CD-KB-001', 'CD-SA-002', 'ST-MG-002'],
                ['SNK-CH-002', 'SNK-OR-003', 'SNK-BB-005', 'SNK-WT-004', 'BEV-PS-005'],
            ],

            // Large transactions (8-15 items) - weekly shopping
            'large' => [
                ['ST-BP-001', 'ST-MG-002', 'ST-GP-003', 'ST-TT-004', 'CD-KB-001', 'CD-SA-002', 'BEV-AQ-002', 'BEV-AQ-002', 'SNK-IG-001', 'SNK-IG-001'],
                ['HH-RD-001', 'HH-ML-002', 'HH-BA-003', 'PC-PP-001', 'PC-LB-002', 'PC-SS-003', 'PC-TP-004', 'BEV-TB-001'],
                ['DE-TA-001', 'DE-KK-002', 'FF-NF-001', 'FF-SN-002', 'ST-MG-002', 'SNK-IG-001', 'SNK-IG-001', 'BEV-AQ-002', 'BEV-AQ-002'],
                ['CIG-SM-001', 'CIG-GG-002', 'CIG-DS-003', 'BEV-TB-001', 'BEV-TB-001', 'SNK-BB-005', 'SNK-BB-005', 'SNK-WT-004', 'SNK-WT-004'],
                ['SNK-IG-001', 'SNK-IG-001', 'SNK-IG-001', 'BEV-AQ-002', 'BEV-AQ-002', 'BEV-KK-003', 'BEV-KK-003', 'SNK-OR-003', 'SNK-CH-002', 'BEV-UM-004'],
            ],
        ];

        // Generate transactions for the past 60 days
        for ($daysAgo = 60; $daysAgo >= 0; $daysAgo--) {
            // Calculate number of transactions for this day
            // More recent days should have more transactions (simulating business growth)
            // Weekends have fewer transactions
            $date = now()->subDays($daysAgo);
            $isWeekend = in_array($date->dayOfWeek, [0, 6]); // 0 = Sunday, 6 = Saturday

            if ($isWeekend) {
                $minTransactions = 5;
                $maxTransactions = 15;
            } else {
                $minTransactions = 10;
                $maxTransactions = 30;
            }

            // More recent days have more transactions
            $growthFactor = 1 + (60 - $daysAgo) / 60; // 1.0 to 2.0
            $transactionCount = rand($minTransactions, $maxTransactions) * $growthFactor;
            $transactionCount = (int) $transactionCount;

            for ($i = 0; $i < $transactionCount; $i++) {
                // Randomly select transaction size (70% small, 25% medium, 5% large)
                $rand = rand(1, 100);
                if ($rand <= 70) {
                    $scenario = $transactionScenarios['small'][array_rand($transactionScenarios['small'])];
                } elseif ($rand <= 95) {
                    $scenario = $transactionScenarios['medium'][array_rand($transactionScenarios['medium'])];
                } else {
                    $scenario = $transactionScenarios['large'][array_rand($transactionScenarios['large'])];
                }

                // Select random user
                $user = $allSalesUsers->random();

                // Create transaction time (random time during store hours: 7 AM - 10 PM)
                $hour = rand(7, 22);
                $minute = rand(0, 59);
                $transactionTime = $date->copy()->setTime($hour, $minute);

                // Prepare transaction details and check stock availability
                $transactionDetails = [];
                $canComplete = true;
                $transactionTotal = 0;

                foreach ($scenario as $sku) {
                    $product = Product::where('sku', $sku)->first();

                    if (!$product) {
                        continue;
                    }

                    // Random quantity (1-5 for most items, 1-2 for expensive items)
                    $quantity = $product->price > 30000 ? rand(1, 2) : rand(1, 5);

                    // Check if enough stock
                    if ($product->current_stock < $quantity) {
                        $canComplete = false;
                        break;
                    }

                    $itemTotal = $product->price * $quantity;
                    $transactionTotal += $itemTotal;

                    $transactionDetails[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'total' => $itemTotal,
                    ];
                }

                // Only create transaction if all items are available
                if ($canComplete && count($transactionDetails) > 0) {
                    // Create transaction
                    $transaction = Transaction::create([
                        'user_id' => $user->id,
                        'total' => $transactionTotal,
                        'created_at' => $transactionTime,
                        'updated_at' => $transactionTime,
                    ]);

                    // Create transaction details and update product stock
                    foreach ($transactionDetails as $detail) {
                        TransactionDetail::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $detail['product']->id,
                            'quantity' => $detail['quantity'],
                            'price' => $detail['price'],
                            'total' => $detail['total'],
                            'created_at' => $transactionTime,
                            'updated_at' => $transactionTime,
                        ]);

                        // Update product stock and total_sold
                        $detail['product']->current_stock -= $detail['quantity'];
                        $detail['product']->total_sold += $detail['quantity'];
                        $detail['product']->save();
                    }
                }
            }
        }
    }
}
