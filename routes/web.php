<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::controller(PaymentController::class)->group(function () {
        Route::get('payment', 'create')->name('payment.create');
        Route::post('payment', 'store')->name('payment.store');
    });
});


Route::controller(PaymentController::class)->group(function () {
    Route::post('paymob/callback', 'callback')->name('payment.callback');
    Route::get('payment/response', 'response')->name('payment.response');
    Route::get('payment/success', 'success')->name('payment.success');
    Route::get('payment/failed', 'failed')->name('payment.failed');
});


require __DIR__.'/auth.php';
