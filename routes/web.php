<?php

use Binjuhor\VNPay\Http\Controllers\VNPayController;

Route::group(['controller' => VNPayController::class, 'middleware' => ['web', 'core']], function () {
    Route::get('payment/vnpay/callback', 'getCallback')->name('payments.vnpay.callback');
    Route::get('payment/vnpay/ipn', 'getIPN')->name('payments.vnpay.ipn');
});
