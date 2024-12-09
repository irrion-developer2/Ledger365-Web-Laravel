<?php

namespace App\Http\Controllers\DesktopApp\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterImportRequest;
use App\Services\Import\TallyStockItemService;
use Illuminate\Http\Request;

class StockItemImportController extends Controller
{

    protected $tallyStockItemService;

    public function __construct(TallyStockItemService $tallyStockItemService)
    {
        $this->tallyStockItemService = $tallyStockItemService;
    }

    public function import(MasterImportRequest $request)
    {
        $response = $this->tallyStockItemService->importStockItemJson($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return response()->json($data, $response->status());
        }

        return $response;
    }

}
