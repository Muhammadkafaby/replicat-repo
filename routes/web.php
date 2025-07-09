<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HotspotController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan rute web untuk aplikasi Anda. Rute-rute ini
| dimuat oleh RouteServiceProvider dan semuanya akan ditetapkan ke
| grup middleware "web". Buat sesuatu yang hebat!
|
*/

/* ======================================= */
/* ======= NASA FIRMS API ROUTES ======= */
/* ======================================= */
Route::prefix('api/hotspots')->group(function () {
    Route::get('/nasa-data', [HotspotController::class, 'getNASAData'])->name('hotspots.nasa-data');
    Route::get('/stats', [HotspotController::class, 'getStats'])->name('hotspots.stats');
    Route::get('/notifications', [HotspotController::class, 'getNotifications'])->name('hotspots.notifications');
});

/* ======================================= */
/* ======= RUTE HALAMAN UTAMA / BERANDA ======= */
/* ======================================= */
Route::get('/', function () {
    return view('home'); // Menampilkan halaman beranda kustom Anda
})->name('home');


Route::get('/peta', function () {
    return view('peta');
});

Route::get('/analitik', function () {
    return view('analitik');
});

Route::get('/laporan', function () {
    return view('laporan');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
/* =========================== */
/* ======= RUTE AUTENTIKASI ======= */
/* =========================== */

// Rute untuk Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Rute untuk Registrasi
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Rute untuk Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


/* ======================================= */
/* ======= RUTE LUPA KATA SANDI ======= */
/* ======================================= */

// Route untuk menampilkan form permintaan reset password
// Terhubung dengan link "Lupa kata sandi?" di halaman login
Route::get('forgot-password', \App\Http\Controllers\Auth\ForgotPasswordController::class . '@showLinkRequestForm')->name('password.request'); // <--- RUTE INI HARUS ADA!

// Route untuk memproses pengiriman email reset password
// Ini adalah target action dari form forgot-password.blade.php
Route::post('forgot-password', \App\Http\Controllers\Auth\ForgotPasswordController::class . '@sendResetLinkEmail')->name('password.email');

// Route untuk menampilkan form reset password (setelah user mengklik link di email)
// {token} adalah parameter token yang dikirim Laravel di URL email
Route::get('reset-password/{token}', \App\Http\Controllers\Auth\ResetPasswordController::class . '@showResetForm')->name('password.reset');

// Route untuk memproses update password baru
Route::post('reset-password', \App\Http\Controllers\Auth\ResetPasswordController::class . '@reset')->name('password.update');


/* ======================================================== */
/* ======= RUTE UNTUK VERIFIKASI EMAIL =================== */
/* ======================================================== */

// Route untuk menampilkan halaman pemberitahuan verifikasi email
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// Route untuk memproses verifikasi email saat user mengklik link di email
Route::get('/email/verify/{id}/{hash}', function (Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('dashboard')->with('status', 'Email Anda berhasil diverifikasi!');
})->middleware(['auth', 'signed'])->name('verification.verify');

// Route untuk mengirim ulang email verifikasi
Route::post('/email/verification-notification', function (Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'Tautan verifikasi baru telah dikirim ke alamat email Anda!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');


/* =========================================== */
/* ======= RUTE YANG DILINDUNGI AUTENTIKASI ======= */
/* =========================================== */

// Rute-rute di dalam grup ini hanya bisa diakses oleh user yang:
// 1. Sudah login (middleware 'auth')
// 2. Sudah memverifikasi emailnya (middleware 'verified')
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard'); // Menampilkan halaman dashboard
    })->name('dashboard');
});

/* ======================================= */
/* ======= RUTE UNTUK TEST TAILWIND CSS ======= */
/* ======================================= */
Route::get('/tailwind-test', function () {
    return view('tailwind-test');
})->name('tailwind.test');

/* ======================================= */
/* ======= RUTE PREVIEW LOGIN/REGISTER ======= */
/* ======================================= */
Route::get('/login-preview', function () {
    return view('auth.login');
})->name('login.preview');

Route::get('/register-preview', function () {
    return view('auth.register');
})->name('register.preview');

/* ======================================= */
/* ======= TEST ROUTES FOR DEBUGGING ======= */
/* ======================================= */
Route::get('/test-register', function () {
    try {
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '081234567890',
            'password' => \Hash::make('password123'),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat',
            'user' => $user
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});