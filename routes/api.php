<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SupplierController;

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
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Caja
    Route::get('/cash-sessions/today', [CashSessionController::class, 'today']);
    Route::post('/cash-sessions/open', [CashSessionController::class, 'open']);
    Route::post('/cash-sessions/{cashSession}/close', [CashSessionController::class, 'close']);

    // Transacciones
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);

    // Categorías y proveedores
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/suppliers', [SupplierController::class, 'index']);

    // Dashboard
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
    Route::get('/dashboard/sales-series', [DashboardController::class, 'salesSeries']);
    Route::get('/dashboard/expenses-by-category', [DashboardController::class, 'expensesByCategory']);

    // Facturas
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
    Route::get('/invoices/upcoming', [InvoiceController::class, 'upcoming']);
});

/*
|--------------------------------------------------------------------------
| Admin only
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/invoices/refresh-statuses', [InvoiceController::class, 'refreshStatuses']);
});