<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotspotController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Hotspot API Routes
Route::prefix('hotspots')->group(function () {
    // Indonesian Hotspot API
    Route::get('/fetch-indonesian', [HotspotController::class, 'fetchIndonesianHotspots']);
    Route::get('/indonesian', [HotspotController::class, 'getHotspots']);
    Route::get('/statistics', [HotspotController::class, 'getStatistics']);
    Route::get('/export', [HotspotController::class, 'exportCsv']);
    
    // NASA FIRMS API (existing)
    Route::get('/nasa', [HotspotController::class, 'getNASAData']);
    Route::get('/demo', [HotspotController::class, 'getDemoData']);
});
