<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API - E-commerce
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Catálogo público
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

// Gestión de productos (solo admin)
Route::middleware(['auth:api', 'is.admin'])->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
});

// Órdenes (usuario autenticado)
Route::middleware('auth:api')->group(function () {
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
});

// Pagos con Stripe
Route::post('payments/webhook', [PaymentController::class, 'webhook']); // público, validado por firma de Stripe
Route::middleware('auth:api')->group(function () {
    Route::post('payments/create-intent', [PaymentController::class, 'createIntent']);
    Route::post('payments/confirm', [PaymentController::class, 'confirm']);
});
