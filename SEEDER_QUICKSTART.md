# Seeder Quick Reference

## Run Seeders
```bash
# Fresh database + seed everything
php artisan migrate:fresh --seed

# Seed only (without migrating)
php artisan db:seed
```

## Test Credentials
| Role        | Username        | Password |
| ----------- | --------------- | -------- |
| Super Admin | superadmin      | password |
| Admin       | ahmad.rifai     | password |
| Admin       | siti.nurhaliza  | password |
| Admin       | budi.santoso    | password |
| Cashier     | dewi.lestari    | password |
| Cashier     | eko.prasetyo    | password |
| Cashier     | fitri.handayani | password |
| Cashier     | gunawan.wijaya  | password |
| Cashier     | heni.kusuma     | password |

## What You Get
- **9 Users**: 1 Super Admin, 3 Admins, 5 Cashiers
- **30 Products**: Across 9 categories (Beverages, Snacks, Personal Care, etc.)
- **100+ Stock Movements**: Over 60 days of stock history
- **800-1,500 Transactions**: Realistic shopping patterns over 60 days

## Key Features
✅ All business rules followed  
✅ Proper stock validation  
✅ Realistic timestamps (60 days of data)  
✅ Transaction patterns (weekday vs weekend)  
✅ Stock movements by admins only  
✅ Price snapshots in transaction details  
✅ Growth simulation (more recent = more transactions)  

## Seeder Execution Order
1. `UserSeeder` - Creates all users
2. `ProductSeeder` - Creates all products  
3. `StockHistorySeeder` - Creates stock movements
4. `TransactionSeeder` - Creates transactions (~30-60 seconds)

## Expected Results
- Dashboard shows revenue trends and statistics
- Reporting module has hundreds of filterable transactions
- POS Terminal displays 30 products with realistic stock
- Stock Management shows complete movement history
- All relationships and foreign keys intact

## Troubleshooting
- **Slow seeding**: Normal for TransactionSeeder (creates 800-1,500 records)
- **Duplicate errors**: Run `php artisan migrate:fresh --seed`
- **Memory issues**: Increase PHP memory limit to 512M

📖 See `database/seeders/README.md` for complete documentation.
