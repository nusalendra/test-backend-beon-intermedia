<?php

use App\Http\Controllers\PembayaranIuranController;
use App\Http\Controllers\RumahController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/rumah', [RumahController::class, 'index']);
Route::post('/rumah', [RumahController::class, 'store']);
Route::get('/rumah/{id}', [RumahController::class, 'show']);
Route::get('/rumah/{id}/edit', [RumahController::class, 'edit']);
Route::post('/rumah/{id}', [RumahController::class, 'update']);
Route::post('/rumah/{id}/perubahan-kepemilikan', [RumahController::class, 'perubahanKepemilikan']);
Route::get('/rumah/{id}/historical-penghuni', [RumahController::class, 'historicalPenghuni']);
Route::get('/rumah/{id}/cek-tagihan', [RumahController::class, 'cekTagihan']);
Route::get('/rumah/{id}/cek-tagihan-tahunan', [RumahController::class, 'cekTagihanTahunan']);

Route::get('/pembayaran-iuran', [PembayaranIuranController::class, 'index']);
Route::post('/pembayaran-iuran', [PembayaranIuranController::class, 'store']);
Route::post('/pembayaran-iuran/{id}/bayar-tagihan-bulanan', [PembayaranIuranController::class, 'bayarTagihanBulanan']);
Route::post('/pembayaran-iuran/bayar-tagihan-tahunan', [PembayaranIuranController::class, 'bayarTagihanTahunan']);
