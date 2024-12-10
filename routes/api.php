<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesktopApp\Import\StockItemImportController;
use App\Http\Controllers\DesktopApp\Import\VoucherTypeImportController;
use App\Http\Controllers\DesktopApp\Import\MasterImportController;
use App\Http\Controllers\DesktopApp\Import\CompanyImportController;
use App\Http\Controllers\DesktopApp\Import\VoucherImportController;

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

//Route::post('/license_check', [LedgerController::class, 'licenseCheckJsonImport'])->name('license.import');

Route::post('/master', [MasterImportController::class, 'import'])->name('master.import');

Route::post('/stock_item', [StockItemImportController::class, 'import'])->name('stockItem.import');

Route::post('/voucher_types', [VoucherTypeImportController::class, 'import'])->name('voucherType.import');

Route::post('/vouchers', [VoucherImportController::class, 'import'])->name('voucher.import');

//Route::post('/reports', [LedgerController::class, 'reportJsonImport'])->name('report.import');
