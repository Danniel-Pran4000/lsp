<?php

use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureApprovedRenter;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'register'])->name('registration.store');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/account', [SessionController::class, 'show'])->name('account.show');
    Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', EnsureApprovedRenter::class])->group(function () {
    Route::match(['get', 'post'], '/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
    Route::post('/rentals/available', [RentalController::class, 'available'])->name('rentals.available');
    Route::match(['get', 'post'], '/rentals/preview', [RentalController::class, 'preview'])->name('rentals.preview');
    Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
});

Route::middleware(['auth', EnsureAdmin::class])->group(function () {
    Route::get('/admin/registrations', [RegistrationController::class, 'index'])->name('registration.index');
    Route::patch('/admin/registrations/{user}', [RegistrationController::class, 'verify'])->name('registration.verify');
    Route::get('/admin/garages/create', [GarageController::class, 'create'])->name('garages.create');
    Route::post('/admin/garages', [GarageController::class, 'storeBatch'])->name('garages.store-batch');
    Route::get('/admin/extensions', [ExtensionController::class, 'activeRentals'])->name('extensions.create');
    Route::get('/admin/extensions/{rental}', [ExtensionController::class, 'preview'])->name('extensions.preview');
    Route::post('/admin/extensions/{rental}', [ExtensionController::class, 'store'])->name('extensions.store');
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('reports.index');
});
