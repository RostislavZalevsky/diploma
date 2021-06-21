<?php

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\LoginController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Account\RegisterController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Webhook\PayPalController;
use App\Http\Controllers\Webhook\StripeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [MainController::class, 'index'])->name('home');

// --- Account
Route::get('/login', [LoginController::class, 'index'])->name('login.index');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('/register', [RegisterController::class, 'index'])->name('register.index');
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/forgot-password', [PasswordResetController::class, 'index'])->name('password.index');
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->name('password.forgot');
Route::get('/reset-password/{token}/{email}', [PasswordResetController::class, 'resetForm'])->name('password.resetForm');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('email.verify');
Route::post('/email/verification', [EmailVerificationController::class, 'verification'])->name('email.verification')->middleware('auth');

// --- Pages
Route::get('/prices', [MainController::class, 'prices'])->name('prices.index');
Route::get('/about', [MainController::class, 'about'])->name('about.index');

// --- Logged
Route::middleware(['auth'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
});

// --- Webhook
Route::prefix('webhook')->group(function () {
    Route::post('/stripe', [StripeController::class, 'handleWebhook']);
    Route::post('/paypal', [PayPalController::class, 'handleWebhook']);
});