<?php

use App\Http\Controllers\Front\WalletRecharge\RechargeInvoiceController;
use App\Http\Controllers\Front\WalletRecharge\WalletRechargeController;
use Illuminate\Support\Facades\Route;

Route::get('/wallet_recharge', [WalletRechargeController::class, 'showPaymentPage'])->name('wallet_recharge_form');
Route::post('/wallet_recharge', [WalletRechargeController::class, 'handlePayment'])
    ->name('wallet_recharge');
Route::get('/wallet_recharge/return', [WalletRechargeController::class, 'handleReturn'])
    ->name('wallet_recharge_return');
Route::get('/wallet_recharge_success', 'App\Http\Controllers\Front\WalletRecharge\WalletRechargeController@paymentSuccess')->name('wallet_recharge_success');
Route::get('/wallet_recharge_fail', 'App\Http\Controllers\Front\WalletRecharge\WalletRechargeController@paymentfail')->name('wallet_recharge_fail');
Route::get('/recharge-invoice/{token}', [RechargeInvoiceController::class, 'show'])->name('recharge_invoice.show');
