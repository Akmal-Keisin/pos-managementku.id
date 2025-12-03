# Point of Sales (POS) Management System

A comprehensive Point of Sales management system built with Laravel 12, Inertia.js, Vue.js 3, and Tailwind CSS with shadcn-vue components.

## Features

### Authentication & Authorization
- **Role-based Access Control** with three roles:
  - **Super Admin**: Full system access including admin management
  - **Admin**: Access to all features except admin management
  - **Cashier**: Access to POS terminal only
  
- Custom login redirect based on user role
- Secure authentication using Laravel Fortify
- Two-factor authentication support

### User Management
- Create, read, update, and delete users
- Role-based user creation (admins can only create cashiers)
- Search functionality
- Soft delete implementation with partial unique indexes
- Email uniqueness validation

### Product Management
- Full CRUD operations for products
- Auto-generated SKU if not provided
- Starting stock support with automatic stock history creation
- Search by product name or SKU
- Soft delete with partial unique indexes
- Track current stock and total sold
- Product images: products can now store a public image path (stored on Laravel `public` disk). Create/Edit forms support image upload and preview; product cards and the POS terminal display the image when available.

### POS Terminal & Cart

**Access**: Super Admin, Admin, Cashier

**Routes**:
- POS Terminal: `/pos-terminal` (grid of product cards)
- Add single product checkout: `POST /pos-terminal/checkout`
- Add to cart: `POST /pos-terminal/add-to-cart`
- Cart view: `/pos-terminal/{user}/cart`
- Cart actions: update `PUT /pos-terminal/{user}/cart/{cartItem}`, remove `DELETE /pos-terminal/{user}/cart/{cartItem}`, clear `DELETE /pos-terminal/{user}/cart/clear`, checkout `POST /pos-terminal/{user}/cart/checkout`

**Features & UX**:
- Grid-based product cards with responsive images, name, SKU, price, stock and total sold.
- Click a product to open a detail `Sheet` (shadcn-vue) showing product details and actions:
    - Add to Cart: add the selected quantity to the authenticated user's cart (cart is scoped per user so multiple cashiers do not conflict).
    - Checkout (single): immediately create a transaction for the single product + quantity.
- Cart page shows each item, allows editing quantity, removing items, clearing the cart, or checking out the whole cart. Checkout creates `transactions` and `transaction_details`, updates product stock and total_sold, and clears the user's cart on success.

**Implementation notes**:
- New migrations were added: `transactions`, `transaction_details`, and `cart_items`.
- Models: `Transaction`, `TransactionDetail`, and `CartItem` represent the transactions and per-user cart.
- Controllers follow the modular pattern:
  - View controller: `PosViewController` (renders POS terminal page)
  - Action controllers: `PosAddToCartController`, `PosCheckoutSingleController` (invokable controllers for actions)
  - Cart module controllers (nested under `Pos/Cart/`): `CartViewController`, `CartUpdateController`, `CartDeleteController`, `CartClearController`, `CartCheckoutController`
- Images uploaded via product forms are stored on the `public` disk and served via the `storage` symlink (`php artisan storage:link`). Old images are deleted when a product's image is replaced.

### Stock Management
- Stock history tracking (increase/decrease)
- Filter by product, admin, and type
- Notes support for stock updates
- Automatic stock validation (prevent negative stock)
- Transaction-based updates for data integrity

### Dashboard
- Admin/Super Admin dashboard (placeholder)
- POS Terminal for cashiers (placeholder)

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Vue.js 3 with TypeScript
- **Routing**: Inertia.js
- **Styling**: Tailwind CSS
- **UI Components**: shadcn-vue
- **Database**: PostgreSQL/MySQL with partial indexes support

## Project Structure

```
app/
├── Console/Commands/
│   └── CreateSuperAdmin.php          # CLI command to create super admin
├── Http/
│   ├── Controllers/
│   │   ├── UserManagement/           # User management controllers
│   │   │   ├── UserManagementViewController.php
│   │   │   ├── UserManagementStoreController.php
│   │   │   ├── UserManagementUpdateController.php
│   │   │   └── UserManagementDeleteController.php
│   │   ├── ProductManagement/        # Product management controllers
│   │   │   ├── ProductManagementViewController.php
│   │   │   ├── ProductManagementStoreController.php
│   │   │   ├── ProductManagementUpdateController.php
│   │   │   └── ProductManagementDeleteController.php
│   │   ├── StockManagement/          # Stock management controllers
│   │   │   ├── StockManagementViewController.php
│   │   │   └── StockManagementUpdateStockController.php
│   │   └── Pos/                      # POS and Cart controllers
│   │       ├── PosViewController.php
│   │       ├── PosAddToCartController.php
│   │       ├── PosCheckoutSingleController.php
│   │       └── Cart/                 # Cart module (nested under Pos)
│   │           ├── CartViewController.php
│   │           ├── CartUpdateController.php
│   │           ├── CartDeleteController.php
│   │           ├── CartClearController.php
│   │           └── CartCheckoutController.php
│   ├── Middleware/
│   │   └── CheckRole.php             # Role-based authorization middleware
│   ├── Requests/
│   │   ├── UserManagement/           # User form validation
│   │   ├── ProductManagement/        # Product form validation
│   │   └── StockManagement/          # Stock form validation
│   └── Responses/
│       └── LoginResponse.php         # Custom login response
├── Models/
│   ├── User.php                      # User model with soft deletes
│   ├── Product.php                   # Product model with auto SKU
│   └── StockHistory.php              # Stock history model
└── Providers/
    ├── AppServiceProvider.php        # Login response binding
    └── FortifyServiceProvider.php    # Custom authentication

database/
├── migrations/
│   ├── *_add_role_to_users_table.php
│   ├── *_create_products_table.php
│   └── *_create_stock_histories_table.php
└── database_structure.md             # Complete database documentation

resources/js/pages/
├── auth/                             # Authentication pages
├── user-management/                  # User management pages
│   ├── Index.vue
│   ├── Create.vue
│   └── Edit.vue
├── product-management/               # Product management pages
│   ├── Index.vue
│   ├── Create.vue
│   └── Edit.vue
├── stock-management/                 # Stock management pages
│   ├── Index.vue
│   └── UpdateStock.vue
├── Dashboard.vue                     # Admin dashboard
└── Pos.vue                          # Cashier POS terminal

routes/
└── web.php                          # All application routes with proper grouping
```

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- PostgreSQL or MySQL database

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd hackathon-pos-managementku.id
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   Update `.env` with your database credentials:
   ```
   DB_CONNECTION=pgsql  # or mysql
   DB_HOST=127.0.0.1
   DB_PORT=5432         # or 3306 for MySQL
   DB_DATABASE=pos_management
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate
   ```

6. **Create Super Admin**
   ```bash
   php artisan create:super-admin
   ```
   
   Follow the prompts to enter:
   - Name
   - Email
   - Password
   - Password Confirmation

7. **Build Frontend Assets**
   
   For development:
   ```bash
   npm run dev
   ```
   
   For production:
   ```bash
   npm run build
   ```

8. **Start the Development Server**
   ```bash
   php artisan serve
   ```
   
   The application will be available at `http://localhost:8000`

## Usage

### Creating Your First Super Admin

After installation, create a super admin account:

```bash
php artisan create:super-admin
```

### Login

1. Navigate to `/login`
2. Enter your credentials
3. You'll be redirected based on your role:
   - Super Admin/Admin → `/dashboard`
   - Cashier → `/pos`

### User Management

**Access**: Super Admin, Admin

**Routes**:
- List: `/user-management`
- Create: `/user-management/create`
- Edit: `/user-management/{id}/edit`
- Delete: `DELETE /user-management/{id}`

**Features**:
- Super admins can manage all users
- Admins can only manage cashiers
- Search by name or email
- Role-based form restrictions

### Product Management

**Access**: Super Admin, Admin

**Routes**:
- List: `/product-management`
- Create: `/product-management/create`
- Edit: `/product-management/{id}/edit`
- Delete: `DELETE /product-management/{id}`

**Features**:
- Auto-generate SKU if not provided
- Set starting stock (creates initial stock history)
- Search by product name or SKU
- Track current stock and total sold

### Stock Management

**Access**: Super Admin, Admin

**Routes**:
- List: `/stock-management`
- Update Stock: `/stock-management/update-stock`

**Features**:
- Increase or decrease stock
- Automatic stock validation
- Filter by product, admin, type
- Notes for stock changes
- Complete audit trail

## Database Schema

See [database/database_structure.md](database/database_structure.md) for complete database documentation including:
- Table structures
- Column definitions
- Constraints and indexes
- Relationships
- Partial unique indexes for soft deletes

## Key Implementation Details

### Partial Unique Indexes

To support soft deletes while maintaining unique constraints:

```sql
-- Users table: email unique only when not deleted
CREATE UNIQUE INDEX unique_email_not_deleted ON users(email) WHERE deleted_at IS NULL;

-- Products table: sku unique only when not deleted  
CREATE UNIQUE INDEX unique_sku_not_deleted ON products(sku) WHERE deleted_at IS NULL;
```

### Auto-Generated SKU

Products automatically generate unique SKUs if not provided:

```php
// In Product model
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($product) {
        if (empty($product->sku)) {
            $product->sku = self::generateUniqueSku();
        }
    });
}
```

### Role-Based Middleware

Routes are protected using the custom `role` middleware:

```php
Route::middleware(['auth', 'role:super-admin,admin'])->group(function () {
    // Admin routes
});

Route::middleware(['auth', 'role:cashier'])->group(function () {
    // Cashier routes
});
```

### Stock Update Transaction

Stock updates use database transactions for data integrity:

```php
DB::beginTransaction();
try {
    // Update product stock
    // Create stock history
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Handle error
}
```

## Development Guidelines

### Controller Organization

Each module follows the pattern:
- `ModuleNameViewController.php` - Handles all view rendering
- `ModuleNameActionController.php` - Invokable controllers for each action (Store, Update, Delete)

### Form Validation

Each module has its own Request directory:
- `ModuleNameStoreRequest.php` - Validation for create
- `ModuleNameUpdateRequest.php` - Validation for update

### Route Grouping

Routes are grouped by module with consistent naming:

```php
Route::prefix('module-name')
    ->name('module-name.')
    ->middleware(['auth', 'verified', 'role:...'])
    ->group(function () {
        // Module routes
    });
```

## Testing

Run the test suite:

```bash
php artisan test
```

## Future Enhancements

- Complete POS terminal implementation
- Sales transaction management
- Receipt printing
- Inventory reports
- Sales analytics
- Low stock alerts
- Barcode scanning support

## Security

- All routes are protected with authentication
- Role-based authorization on routes and form requests
- CSRF protection on all forms
- Password hashing using bcrypt
- SQL injection prevention via Eloquent  ORM
- XSS protection via Vue.js escaping

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License.

## Support

For support, email akmalkeisin@gmail.com or open an issue in the repository.
