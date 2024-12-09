<?php

namespace App\Http\Controllers\DesktopApp\Import;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterImportRequest;
use App\Services\Import\TallyVoucherTypeService;

class VoucherTypeImportController extends Controller
{

    protected $tallyVoucherTypeService;

    public function __construct(TallyVoucherTypeService $tallyVoucherTypeService)
    {
        $this->tallyVoucherTypeService = $tallyVoucherTypeService;
    }

    public function import(MasterImportRequest $request)
    {
        $response = $this->tallyVoucherTypeService->importVoucherTypeJson($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return response()->json($data, $response->status());
        }

        return $response;
    }

}
