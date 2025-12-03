<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Beverages
            [
                'name' => 'Teh Botol Sosro',
                'sku' => 'BEV-TB-001',
                'price' => 5000,
                'description' => 'Minuman teh dalam botol kemasan 350ml',
                'current_stock' => 150,
                'total_sold' => 0,
            ],
            [
                'name' => 'Aqua 600ml',
                'sku' => 'BEV-AQ-002',
                'price' => 3500,
                'description' => 'Air mineral kemasan 600ml',
                'current_stock' => 200,
                'total_sold' => 0,
            ],
            [
                'name' => 'Kopi Kapal Api',
                'sku' => 'BEV-KK-003',
                'price' => 2000,
                'description' => 'Kopi sachet instant',
                'current_stock' => 100,
                'total_sold' => 0,
            ],
            [
                'name' => 'Ultra Milk Coklat',
                'sku' => 'BEV-UM-004',
                'price' => 7000,
                'description' => 'Susu UHT rasa coklat 250ml',
                'current_stock' => 80,
                'total_sold' => 0,
            ],
            [
                'name' => 'Pocari Sweat',
                'sku' => 'BEV-PS-005',
                'price' => 8000,
                'description' => 'Minuman isotonik 350ml',
                'current_stock' => 60,
                'total_sold' => 0,
            ],

            // Snacks
            [
                'name' => 'Indomie Goreng',
                'sku' => 'SNK-IG-001',
                'price' => 3500,
                'description' => 'Mie instan rasa goreng',
                'current_stock' => 180,
                'total_sold' => 0,
            ],
            [
                'name' => 'Chitato Sapi Panggang',
                'sku' => 'SNK-CH-002',
                'price' => 10000,
                'description' => 'Keripik kentang rasa sapi panggang 68g',
                'current_stock' => 50,
                'total_sold' => 0,
            ],
            [
                'name' => 'Oreo Original',
                'sku' => 'SNK-OR-003',
                'price' => 12000,
                'description' => 'Biskuit sandwich coklat',
                'current_stock' => 45,
                'total_sold' => 0,
            ],
            [
                'name' => 'Wafer Tango',
                'sku' => 'SNK-WT-004',
                'price' => 1500,
                'description' => 'Wafer coklat',
                'current_stock' => 120,
                'total_sold' => 0,
            ],
            [
                'name' => 'Beng Beng',
                'sku' => 'SNK-BB-005',
                'price' => 2500,
                'description' => 'Wafer coklat dengan karamel',
                'current_stock' => 90,
                'total_sold' => 0,
            ],

            // Personal Care
            [
                'name' => 'Pepsodent 190g',
                'sku' => 'PC-PP-001',
                'price' => 12000,
                'description' => 'Pasta gigi whitening',
                'current_stock' => 40,
                'total_sold' => 0,
            ],
            [
                'name' => 'Lifebuoy Sabun Batang',
                'sku' => 'PC-LB-002',
                'price' => 4000,
                'description' => 'Sabun mandi batang total 10',
                'current_stock' => 70,
                'total_sold' => 0,
            ],
            [
                'name' => 'Sunsilk Shampoo 170ml',
                'sku' => 'PC-SS-003',
                'price' => 18000,
                'description' => 'Shampo hitam berkilau',
                'current_stock' => 35,
                'total_sold' => 0,
            ],
            [
                'name' => 'Tissue Paseo 250s',
                'sku' => 'PC-TP-004',
                'price' => 15000,
                'description' => 'Tissue wajah 250 lembar',
                'current_stock' => 25,
                'total_sold' => 0,
            ],

            // Household
            [
                'name' => 'Rinso Deterjen 800g',
                'sku' => 'HH-RD-001',
                'price' => 25000,
                'description' => 'Deterjen bubuk anti noda',
                'current_stock' => 30,
                'total_sold' => 0,
            ],
            [
                'name' => 'Mama Lemon 800ml',
                'sku' => 'HH-ML-002',
                'price' => 16000,
                'description' => 'Sabun cuci piring ekstrak jeruk nipis',
                'current_stock' => 40,
                'total_sold' => 0,
            ],
            [
                'name' => 'Baygon Aerosol',
                'sku' => 'HH-BA-003',
                'price' => 35000,
                'description' => 'Obat nyamuk semprot 600ml',
                'current_stock' => 20,
                'total_sold' => 0,
            ],

            // Cigarettes
            [
                'name' => 'Sampoerna Mild 16',
                'sku' => 'CIG-SM-001',
                'price' => 28000,
                'description' => 'Rokok mild 16 batang',
                'current_stock' => 100,
                'total_sold' => 0,
            ],
            [
                'name' => 'Gudang Garam Filter',
                'sku' => 'CIG-GG-002',
                'price' => 25000,
                'description' => 'Rokok kretek filter 12 batang',
                'current_stock' => 85,
                'total_sold' => 0,
            ],
            [
                'name' => 'Djarum Super 12',
                'sku' => 'CIG-DS-003',
                'price' => 19000,
                'description' => 'Rokok kretek 12 batang',
                'current_stock' => 75,
                'total_sold' => 0,
            ],

            // Dairy & Eggs
            [
                'name' => 'Telur Ayam Negeri 10pcs',
                'sku' => 'DE-TA-001',
                'price' => 22000,
                'description' => 'Telur ayam negeri segar 10 butir',
                'current_stock' => 50,
                'total_sold' => 0,
            ],
            [
                'name' => 'Keju Kraft Singles',
                'sku' => 'DE-KK-002',
                'price' => 45000,
                'description' => 'Keju lembaran 10 slices',
                'current_stock' => 15,
                'total_sold' => 0,
            ],

            // Frozen Food
            [
                'name' => 'Nugget Fiesta 500g',
                'sku' => 'FF-NF-001',
                'price' => 32000,
                'description' => 'Nugget ayam beku 500g',
                'current_stock' => 25,
                'total_sold' => 0,
            ],
            [
                'name' => 'Sosis So Nice 500g',
                'sku' => 'FF-SN-002',
                'price' => 38000,
                'description' => 'Sosis ayam beku 500g',
                'current_stock' => 20,
                'total_sold' => 0,
            ],

            // Staples
            [
                'name' => 'Beras Pandan Wangi 5kg',
                'sku' => 'ST-BP-001',
                'price' => 75000,
                'description' => 'Beras premium 5kg',
                'current_stock' => 40,
                'total_sold' => 0,
            ],
            [
                'name' => 'Minyak Goreng Tropical 2L',
                'sku' => 'ST-MG-002',
                'price' => 32000,
                'description' => 'Minyak goreng kelapa sawit 2 liter',
                'current_stock' => 35,
                'total_sold' => 0,
            ],
            [
                'name' => 'Gula Pasir 1kg',
                'sku' => 'ST-GP-003',
                'price' => 15000,
                'description' => 'Gula pasir putih 1kg',
                'current_stock' => 60,
                'total_sold' => 0,
            ],
            [
                'name' => 'Tepung Terigu Segitiga Biru 1kg',
                'sku' => 'ST-TT-004',
                'price' => 12000,
                'description' => 'Tepung terigu protein sedang 1kg',
                'current_stock' => 45,
                'total_sold' => 0,
            ],

            // Condiments
            [
                'name' => 'Kecap Bango 220ml',
                'sku' => 'CD-KB-001',
                'price' => 14000,
                'description' => 'Kecap manis 220ml',
                'current_stock' => 55,
                'total_sold' => 0,
            ],
            [
                'name' => 'Saus ABC 340ml',
                'sku' => 'CD-SA-002',
                'price' => 12000,
                'description' => 'Saus tomat 340ml',
                'current_stock' => 48,
                'total_sold' => 0,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
