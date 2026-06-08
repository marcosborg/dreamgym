<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Models\Room;

Route::get('/', function () {
    $room = Room::query()->where('is_active', true)->first();

    return view('home', compact('room'));
})->name('home');

Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');

Route::get('/book', [BookingController::class, 'index'])->name('bookings.index');
Route::post('/book', [BookingController::class, 'store'])->name('bookings.store');
Route::post('/purchase', [PurchaseController::class, 'store'])->name('purchase.store');
Route::get('/purchase/{payment}/checkout', [PurchaseController::class, 'checkout'])->middleware('auth')->name('purchase.checkout');
Route::post('/purchase/{payment}/complete', [PurchaseController::class, 'complete'])->middleware('auth')->name('purchase.complete');
Route::get('/purchase/{payment}/confirmed', [PurchaseController::class, 'confirmed'])->middleware('auth')->name('purchase.confirmed');
Route::get('/checkout/{booking}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/{booking}/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');
Route::get('/booking/{booking}/confirmed', [CheckoutController::class, 'confirmed'])->name('booking.confirmed');
Route::get('/lang/{locale}', LocaleController::class)->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.store');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [CustomerAuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/account', [AccountController::class, 'dashboard'])->middleware('auth')->name('account.dashboard');
