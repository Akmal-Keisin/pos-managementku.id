<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagement\UserManagementViewController;
use App\Http\Controllers\UserManagement\UserManagementStoreController;
use App\Http\Controllers\UserManagement\UserManagementUpdateController;
use App\Http\Controllers\UserManagement\UserManagementDeleteController;
use App\Http\Controllers\ProductManagement\ProductManagementViewController;
use App\Http\Controllers\ProductManagement\ProductManagementStoreController;
use App\Http\Controllers\ProductManagement\ProductManagementUpdateController;
use App\Http\Controllers\ProductManagement\ProductManagementDeleteController;
use App\Http\Controllers\StockManagement\StockManagementViewController;
use App\Http\Controllers\StockManagement\StockManagementUpdateStockController;
use App\Http\Controllers\Reporting\ReportingViewController;
use App\Http\Controllers\Pos\PosViewController;
use App\Http\Controllers\Pos\PosAddToCartController;
use App\Http\Controllers\Pos\PosCheckoutSingleController;
use App\Http\Controllers\Pos\Cart\CartViewController;
use App\Http\Controllers\Pos\Cart\CartUpdateController;
use App\Http\Controllers\Pos\Cart\CartDeleteController;
use App\Http\Controllers\Pos\Cart\CartClearController;
use App\Http\Controllers\Pos\Cart\CartCheckoutController;
use App\Http\Controllers\Chatbot\ChatbotViewController;
use App\Http\Controllers\Chatbot\ChatbotTopicStoreController;
use App\Http\Controllers\Chatbot\ChatbotTopicDeleteController;
use App\Http\Controllers\Chatbot\ChatbotTopicClearController;
use App\Http\Controllers\Chatbot\ChatbotMessageStoreController;
use App\Http\Controllers\Chatbot\ChatbotToolController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Dashboard - accessible by super-admin and admin
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:super-admin,admin'])
    ->name('dashboard');

// POS Terminal - accessible by super-admin, admin, cashier
Route::prefix('pos-terminal')->name('pos-terminal.')->middleware(['auth', 'verified', 'role:super-admin,admin,cashier'])->group(function () {
    Route::get('/', [PosViewController::class, 'index'])->name('index');
    Route::post('/add-to-cart', PosAddToCartController::class)->name('add-to-cart');
    Route::post('/checkout', PosCheckoutSingleController::class)->name('checkout-single');

    // Cart routes
    Route::get('/{user}/cart', [CartViewController::class, 'show'])->name('cart.show');
    Route::put('/{user}/cart/{cartItem}', CartUpdateController::class)->name('cart.update');
    Route::delete('/{user}/cart/{cartItem}', CartDeleteController::class)->name('cart.destroy');
    Route::post('/{user}/cart/checkout', CartCheckoutController::class)->name('cart.checkout');
    Route::delete('/{user}/cart/clear', CartClearController::class)->name('cart.clear');
});

// User Management - accessible by super-admin and admin
Route::prefix('user-management')->name('user-management.')->middleware(['auth', 'verified', 'role:super-admin,admin'])->group(function () {
    Route::get('/', [UserManagementViewController::class, 'index'])->name('index');
    Route::get('/create', [UserManagementViewController::class, 'create'])->name('create');
    Route::post('/', UserManagementStoreController::class)->name('store');
    Route::get('/{user}/edit', [UserManagementViewController::class, 'edit'])->name('edit');
    Route::put('/{user}', UserManagementUpdateController::class)->name('update');
    Route::delete('/{user}', UserManagementDeleteController::class)->name('destroy');
});

// Product Management - accessible by super-admin and admin
Route::prefix('product-management')->name('product-management.')->middleware(['auth', 'verified', 'role:super-admin,admin'])->group(function () {
    Route::get('/', [ProductManagementViewController::class, 'index'])->name('index');
    Route::get('/create', [ProductManagementViewController::class, 'create'])->name('create');
    Route::post('/', ProductManagementStoreController::class)->name('store');
    Route::get('/{product}/edit', [ProductManagementViewController::class, 'edit'])->name('edit');
    Route::put('/{product}', ProductManagementUpdateController::class)->name('update');
    Route::delete('/{product}', ProductManagementDeleteController::class)->name('destroy');
});

// Stock Management - accessible by super-admin and admin
Route::prefix('stock-management')->name('stock-management.')->middleware(['auth', 'verified', 'role:super-admin,admin'])->group(function () {
    Route::get('/', [StockManagementViewController::class, 'index'])->name('index');
    Route::get('/update-stock', [StockManagementViewController::class, 'updateStock'])->name('update-stock');
    Route::post('/update-stock', StockManagementUpdateStockController::class)->name('update-stock.store');
});

// Reporting - accessible by super-admin and admin
Route::prefix('reporting')->name('reporting.')->middleware(['auth', 'verified', 'role:super-admin,admin'])->group(function () {
    Route::get('/', [ReportingViewController::class, 'index'])->name('index');
    Route::get('/{transaction}', [ReportingViewController::class, 'show'])->name('show');
});

// Chatbot - accessible by all authenticated and verified users
Route::prefix('chatbot')->name('chatbot.')->middleware(['auth', 'verified', 'role:super-admin,admin'])->group(function () {
    Route::get('/', [ChatbotViewController::class, 'index'])->name('index');

    // Topics
    Route::post('/topics', ChatbotTopicStoreController::class)->name('topics.store');
    Route::delete('/topics/{topic}', ChatbotTopicDeleteController::class)->name('topics.destroy');
    Route::delete('/topics/{topic}/clear', ChatbotTopicClearController::class)->name('topics.clear');

    // Messages
    Route::post('/topics/{topic}/messages', ChatbotMessageStoreController::class)->name('messages.store');

    // Tool endpoint for chatbot to request structured DB data (whitelisted)
    Route::post('/tool/db', ChatbotToolController::class)->name('tool.db');
});

require __DIR__ . '/settings.php';
