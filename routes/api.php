<?php

use App\Http\Controllers\DesktopApp\Import\CompanyImportController;
use App\Http\Controllers\DesktopApp\Import\LedgerController;
use App\Http\Controllers\SuperAdmin\TallyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/companies', [CompanyImportController::class, 'import'])->name('companies.import');

Route::post('/license_check', [LedgerController::class, 'licenseCheckJsonImport'])->name('license.import');

Route::post('/master', [LedgerController::class, 'masterJsonImport'])->name('master.import');

Route::post('/stock_item', [LedgerController::class, 'stockItemJsonImport'])->name('stockItem.import');

Route::post('/voucher_types', [LedgerController::class, 'voucherTypeJsonImport'])->name('voucherType.import');

Route::post('/vouchers', [LedgerController::class, 'voucherJsonImport'])->name('voucher.import');

Route::post('/reports', [LedgerController::class, 'reportJsonImport'])->name('report.import');
