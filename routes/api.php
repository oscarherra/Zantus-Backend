<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\ShoppingItemController;
use App\Http\Controllers\Api\CreditController;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Caja
    |--------------------------------------------------------------------------
    */
    Route::get('/cash-sessions', [CashSessionController::class, 'index']);
    Route::get('/cash-sessions/current', [CashSessionController::class, 'current']);
    Route::post('/cash-sessions/open', [CashSessionController::class, 'open']);
    Route::post('/cash-sessions/{cashSession}/close', [CashSessionController::class, 'close']);

    /*
    |--------------------------------------------------------------------------
    | Transacciones
    |--------------------------------------------------------------------------
    */
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Categorías y proveedores
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/suppliers', [SupplierController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
    Route::get('/dashboard/sales-series', [DashboardController::class, 'salesSeries']);
    Route::get('/dashboard/expenses-by-category', [DashboardController::class, 'expensesByCategory']);

    /*
    |--------------------------------------------------------------------------
    | Facturas
    |--------------------------------------------------------------------------
    */
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
    Route::get('/invoices/upcoming', [InvoiceController::class, 'upcoming']);
    Route::post('/invoices/refresh-statuses', [InvoiceController::class, 'refreshStatuses']);

    /*
    |--------------------------------------------------------------------------
    | Lista de compras
    |--------------------------------------------------------------------------
    | Importante: /shopping-items/done debe ir antes de /shopping-items/{shoppingItem}
    |--------------------------------------------------------------------------
    */
    Route::get('/shopping-items', [ShoppingItemController::class, 'index']);
    Route::post('/shopping-items', [ShoppingItemController::class, 'store']);
    Route::patch('/shopping-items/{shoppingItem}', [ShoppingItemController::class, 'update']);
    Route::delete('/shopping-items/done', [ShoppingItemController::class, 'clearDone']);
    Route::delete('/shopping-items/{shoppingItem}', [ShoppingItemController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Fiados
    |--------------------------------------------------------------------------
    */
    Route::get('/credits/customers', [CreditController::class, 'customers']);
    Route::post('/credits/customers', [CreditController::class, 'storeCustomer']);

    Route::get('/credits', [CreditController::class, 'index']);
    Route::post('/credits', [CreditController::class, 'store']);
    Route::post('/credits/{credit}/pay', [CreditController::class, 'pay']);
    Route::get('/credits-summary', [CreditController::class, 'summary']);
});

/*
|--------------------------------------------------------------------------
| Admin only
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Por ahora esta ruta también está arriba para usuarios autenticados.
    // Si querés que SOLO el admin pueda refrescar estados, eliminá la ruta de arriba.
    Route::post('/admin/invoices/refresh-statuses', [InvoiceController::class, 'refreshStatuses']);
});