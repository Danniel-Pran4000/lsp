<?php

use App\Http\Controllers\AdminRentalController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureApprovedRenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Arahkan pengunjung ke halaman masuk atau daftar utama sesuai peran akun.
Route::get('/', function (Request $request): RedirectResponse {
    return to_route(match ($request->user()?->role) {
        'admin' => 'registration.index',
        'user' => 'rentals.index',
        default => 'login',
    });
});

// Pendaftaran dan login tersedia bagi pengunjung yang belum memiliki sesi.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'showForm'])->name('register');
    Route::post('/register', [RegistrationController::class, 'registerRenter'])->name('registration.store');

    Route::get('/login', [SessionController::class, 'showLogin'])->name('login');
    Route::post('/login', [SessionController::class, 'startSession'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

// Sesi diperlukan untuk membuka riwayat. Controller memeriksa hak akses setiap rincian dan foto.
Route::middleware('auth')->group(function () {
    Route::get('/account', [SessionController::class, 'redirectAccount'])->name('account.show');
    Route::get('/rentals', [RentalController::class, 'listRentals'])->name('rentals.index');

    // ID numerik memisahkan URL rincian dari URL formulir seperti /rentals/create.
    Route::get('/rentals/{rental}', [RentalController::class, 'showRental'])
        ->whereNumber('rental')
        ->name('rentals.show');
    Route::get('/rentals/{rental}/proof/{extension?}', [RentalController::class, 'serveProof'])
        ->whereNumber(['rental', 'extension'])
        ->name('rentals.proof');

    Route::post('/logout', [SessionController::class, 'endSession'])->name('logout');
});

// Persetujuan akun diperlukan sejak penyusunan rencana hingga konfirmasi sewa baru.
Route::middleware(['auth', EnsureApprovedRenter::class])->group(function () {
    Route::get('/rentals/create', [RentalController::class, 'showForm'])->name('rentals.create');
    Route::post('/rentals/available', [RentalController::class, 'findUnits'])->name('rentals.available');

    // POST menerima rincian sewa. GET membuka pratinjau setelah pengalihan pada alur tanpa JavaScript.
    Route::match(['get', 'post'], '/rentals/preview', [RentalController::class, 'previewRental'])->name('rentals.preview');
    Route::post('/rentals', [RentalController::class, 'saveRental'])->name('rentals.store');
});

// Batasi pengelolaan pendaftar, unit, perpanjangan, dan laporan kepada admin.
Route::middleware(['auth', EnsureAdmin::class])->group(function () {
    Route::get('/admin/rentals', [AdminRentalController::class, 'listRentals'])->name('admin.rentals');

    Route::get('/admin/users', [RegistrationController::class, 'listRenters'])->name('registration.index');
    Route::patch('/admin/users/{user}', [RegistrationController::class, 'reviewRenter'])->name('registration.verify');

    // Pertahankan URL lama untuk daftar pendaftar dan tindakan verifikasi.
    Route::get('/admin/registrations', fn (): RedirectResponse => to_route('registration.index'));
    Route::patch('/admin/registrations/{user}', [RegistrationController::class, 'reviewRenter']);

    Route::get('/admin/garages', [GarageController::class, 'listGarages'])->name('garages.index');
    Route::get('/admin/garages/create', [GarageController::class, 'showForm'])->name('garages.create');
    Route::post('/admin/garages', [GarageController::class, 'createUnits'])->name('garages.store-batch');

    Route::get('/admin/extensions', [ExtensionController::class, 'activeRentals'])->name('extensions.create');
    Route::get('/admin/extensions/{rental}', [ExtensionController::class, 'previewExtension'])->name('extensions.preview');
    Route::post('/admin/extensions/{rental}', [ExtensionController::class, 'saveExtension'])->name('extensions.store');

    Route::get('/admin/reports', [ReportController::class, 'showReport'])->name('reports.index');
});
