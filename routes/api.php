<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\ExchangeRateController;


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:api')->get('profile', [AuthController::class, 'profile']);
   
});

 Route::middleware('auth:api')->get('/transactions', [TransactionController::class, 'index']);




Route::middleware('auth:api')->group(function () {
    Route::post('/wallet/fund', [WalletController::class, 'fund']);
    Route::post('/wallet/transfer', [WalletController::class, 'transfer']); 
});


Route::middleware('auth:api')->get('/wallet/transactions', [TransactionController::class, 'index']);

Route::middleware('auth:api')->get('/exchange-rate', [ExchangeRateController::class, 'getRate']);

