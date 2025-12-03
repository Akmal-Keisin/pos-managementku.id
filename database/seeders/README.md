# Database Seeders Documentation

## Overview

This project includes comprehensive seeders that simulate a fully operational POS (Point of Sale) system with realistic data spanning 60 days of business activity.

## What Gets Seeded

### 1. **Users** (9 total users)
- **1 Super Admin**: `superadmin` / `password`
- **3 Admins**:
  - Ahmad Rifai (`ahmad.rifai` / `password`)
  - Siti Nurhaliza (`siti.nurhaliza` / `password`)
  - Budi Santoso (`budi.santoso` / `password`)
- **5 Cashiers**:
  - Dewi Lestari (`dewi.lestari` / `password`)
  - Eko Prasetyo (`eko.prasetyo` / `password`)
  - Fitri Handayani (`fitri.handayani` / `password`)
  - Gunawan Wijaya (`gunawan.wijaya` / `password`)
  - Heni Kusuma (`heni.kusuma` / `password`)

### 2. **Products** (30 products across 9 categories)

#### Beverages (5 products)
- Teh Botol Sosro (IDR 5,000)
- Aqua 600ml (IDR 3,500)
- Kopi Kapal Api (IDR 2,000)
- Ultra Milk Coklat (IDR 7,000)
- Pocari Sweat (IDR 8,000)

#### Snacks (5 products)
- Indomie Goreng (IDR 3,500)
- Chitato Sapi Panggang (IDR 10,000)
- Oreo Original (IDR 12,000)
- Wafer Tango (IDR 1,500)
- Beng Beng (IDR 2,500)

#### Personal Care (4 products)
- Pepsodent 190g (IDR 12,000)
- Lifebuoy Sabun Batang (IDR 4,000)
- Sunsilk Shampoo 170ml (IDR 18,000)
- Tissue Paseo 250s (IDR 15,000)

#### Household (3 products)
- Rinso Deterjen 800g (IDR 25,000)
- Mama Lemon 800ml (IDR 16,000)
- Baygon Aerosol (IDR 35,000)

#### Cigarettes (3 products)
- Sampoerna Mild 16 (IDR 28,000)
- Gudang Garam Filter (IDR 25,000)
- Djarum Super 12 (IDR 19,000)

#### Dairy & Eggs (2 products)
- Telur Ayam Negeri 10pcs (IDR 22,000)
- Keju Kraft Singles (IDR 45,000)

#### Frozen Food (2 products)
- Nugget Fiesta 500g (IDR 32,000)
- Sosis So Nice 500g (IDR 38,000)

#### Staples (4 products)
- Beras Pandan Wangi 5kg (IDR 75,000)
- Minyak Goreng Tropical 2L (IDR 32,000)
- Gula Pasir 1kg (IDR 15,000)
- Tepung Terigu Segitiga Biru 1kg (IDR 12,000)

#### Condiments (2 products)
- Kecap Bango 220ml (IDR 14,000)
- Saus ABC 340ml (IDR 12,000)

### 3. **Stock History** (100+ records)
- Initial stock records for all products (60 days ago)
- 25+ stock movements (increases and decreases) spread over 60 days
- Realistic scenarios:
  - Regular restocking by admins
  - Damage/expiry adjustments
  - Quality control deductions
  - High-demand item restocking

### 4. **Transactions** (800-1,500+ transactions)
- Transactions created by cashiers and admins over 60 days
- Business growth simulation (more recent days have more transactions)
- Weekend vs weekday patterns (fewer transactions on weekends)
- Three transaction types:
  - **Small** (1-3 items) - 70% of transactions
  - **Medium** (4-7 items) - 25% of transactions
  - **Large** (8-15 items) - 5% of transactions (weekly shopping)
- Realistic scenarios:
  - Quick purchases (single beverage or snack)
  - Daily shopping (few items)
  - Weekly grocery shopping (bulk items)
  - Mixed category purchases
- Random transaction times during store hours (7 AM - 10 PM)
- All transactions follow business rules:
  - Stock validation (only creates if stock available)
  - Automatic stock deduction
  - Automatic total_sold increment
  - Proper total calculation
  - TransactionDetail creation with correct price snapshots

## Running the Seeders

### Prerequisites
```bash
# Make sure you have a fresh database
php artisan migrate:fresh
```

### Run All Seeders
```bash
php artisan db:seed
```

This will execute all seeders in the correct order:
1. `UserSeeder` - Creates all users
2. `ProductSeeder` - Creates all products
3. `StockHistorySeeder` - Creates stock movements
4. `TransactionSeeder` - Creates transactions (this takes ~30-60 seconds)

### Run Individual Seeders
```bash
# Users only
php artisan db:seed --class=UserSeeder

# Products only
php artisan db:seed --class=ProductSeeder

# Stock history only (requires Users and Products)
php artisan db:seed --class=StockHistorySeeder

# Transactions only (requires Users and Products)
php artisan db:seed --class=TransactionSeeder
```

### Fresh Migration + Seed
```bash
# Reset database and run all seeders
php artisan migrate:fresh --seed
```

## Business Rules Implemented

### User Management
✅ Super Admin can create Admins and Cashiers  
✅ Admin can create Cashiers only  
✅ All users are email verified by default  
✅ Password hashing using Laravel Hash  

### Product Management
✅ Unique SKU generation (format: CATEGORY-CODE-NUMBER)  
✅ Stock tracking (current_stock and total_sold)  
✅ Price stored as decimal(15,2)  
✅ Soft deletes enabled  

### Stock Management
✅ Only Admins and Super Admins can manage stock  
✅ Two types: 'increase' and 'decrease'  
✅ Stock history tracks user who made the change  
✅ Timestamped records for audit trail  

### Transaction Management
✅ Cashiers and Admins can create transactions  
✅ Stock validation before transaction creation  
✅ Automatic stock deduction on purchase  
✅ Automatic total_sold increment  
✅ Transaction total = sum of all detail totals  
✅ TransactionDetail total = price × quantity  
✅ Price snapshot in TransactionDetail (preserves historical prices)  

## Expected Results

After seeding, you should see:

### Dashboard
- Total revenue from all transactions
- Today's revenue and transactions
- Product count and low stock alerts
- Revenue trends over 7 days
- Top selling products
- Recent transactions and stock movements

### Reporting Module
- Filterable transaction list
- Transaction details with items
- Date range filtering works
- User-based filtering works
- Product-based filtering works

### POS Terminal
- 30 products available with varied stock levels
- Products with low stock badges (< 10 units)
- Some products may be out of stock (depending on transaction volume)
- Realistic product catalog with Indonesian products

### Stock Management
- Stock history showing initial stock
- Restocking records over 60 days
- Damage/quality control deductions
- Notes for each movement

## Data Integrity

All seeders maintain data integrity:

✅ **Foreign Key Constraints**: All relationships are valid  
✅ **Stock Consistency**: Product stock = initial + increases - decreases - sold  
✅ **Transaction Totals**: All totals calculated correctly  
✅ **Timestamps**: Realistic timestamps spread over 60 days  
✅ **Business Hours**: Transactions only during 7 AM - 10 PM  
✅ **Role Permissions**: Users only perform actions allowed by their role  

## Troubleshooting

### "Too many connections" or "Memory limit"
The TransactionSeeder creates many records. If you encounter issues:
- Increase PHP memory limit in `php.ini`: `memory_limit = 512M`
- Run seeders individually with breaks between them

### "Duplicate entry" errors
This shouldn't happen with fresh migration, but if it does:
```bash
php artisan migrate:fresh --seed
```

### Slow seeding
The TransactionSeeder is intentionally comprehensive and may take 30-60 seconds. This is normal. It creates 800-1,500+ transactions with proper validation and stock updates.

### No transactions created
Check if products have sufficient stock. The seeder validates stock before each transaction. If all products are out of stock, new transactions won't be created.

## Customization

### Adjust Transaction Volume
Edit `TransactionSeeder.php`:
```php
// Line 38-51: Adjust min/max transactions per day
$minTransactions = 10; // Increase for more data
$maxTransactions = 30; // Increase for more data
```

### Add More Products
Edit `ProductSeeder.php` and add more products to the `$products` array.

### Change Time Range
Edit `TransactionSeeder.php`:
```php
// Line 73: Change 60 to desired number of days
for ($daysAgo = 60; $daysAgo >= 0; $daysAgo--) {
```

### Add More Users
Edit `UserSeeder.php` and add more admins or cashiers to their respective arrays.

## Testing Credentials

All users have the password: `password`

**Test Accounts:**
- Super Admin: `superadmin` / `password`
- Admin: `ahmad.rifai` / `password`
- Cashier: `dewi.lestari` / `password`

## Notes

- All monetary values are in Indonesian Rupiah (IDR)
- Product names are Indonesian brands/products
- Stock levels are realistic for a small retail store
- Transaction patterns simulate real shopping behavior
- The data is production-ready for demonstrations and testing
