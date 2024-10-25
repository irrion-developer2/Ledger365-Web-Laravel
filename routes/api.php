<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\TallyController;
use App\Http\Controllers\SuperAdmin\LedgerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/license_check', [LedgerController::class, 'licenseCheckJsonImport'])->name('license.import');

Route::post('/companies', [LedgerController::class, 'companyJsonImport'])->name('company.import');

Route::post('/master', [LedgerController::class, 'masterJsonImport'])->name('master.import');

Route::post('/stock_item', [LedgerController::class, 'stockItemJsonImport'])->name('stockItem.import');

Route::post('/vouchers', [LedgerController::class, 'voucherJsonImport'])->name('voucher.import');

Route::post('/reports', [LedgerController::class, 'reportJsonImport'])->name('report.import');
